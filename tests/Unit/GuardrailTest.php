<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Quay\Tools\Guardrails\ChangedFiles;
use Quay\Tools\Guardrails\Packages;
use Quay\Tools\Guardrails\PullRequestSize;
use Quay\Tools\Guardrails\Suppressions;
use Quay\Tools\Guardrails\TestChanges;
use Symfony\Component\Process\Process;

final class GuardrailTest extends TestCase
{
    private const string FROZEN_IGNORE = <<<'NEON'
        parameters:
            ignoreErrors:
                -
                    identifier: staticMethod.dynamicCall
        NEON;

    public function test_adding_an_ignore_comment_fails_and_deleting_one_passes(): void
    {
        $base = ['app/Example.php' => "<?php\n\$value = 1;\n"];
        $added = ['app/Example.php' => "<?php\n/** @phpstan-ignore-next-line */\n\$value = 1;\n"];
        $addedResult = Suppressions::compare($base, $added);
        $this->assertTrue($addedResult['fail']);
        $this->assertNotSame([], $addedResult['added']);

        $deleted = Suppressions::compare($added, $base);
        $this->assertFalse($deleted['fail']);
        $this->assertNotSame([], $deleted['removed']);
    }

    public function test_moving_an_ignore_comment_fails(): void
    {
        $base = ['app/Example.php' => "<?php\n/** @phpstan-ignore-next-line */\n\$value = 1;\n"];
        $moved = ['app/Example.php' => "<?php\n\$value = 1;\n/** @phpstan-ignore-next-line */\n"];
        $result = Suppressions::compare($base, $moved);

        $this->assertTrue($result['fail']);
    }

    public function test_changing_the_frozen_phpstan_ignore_fails_and_removing_it_passes(): void
    {
        $changed = <<<'NEON'
            parameters:
                ignoreErrors:
                    -
                        identifier: argument.type
            NEON;
        $removed = "parameters:\n    level: max\n";

        $this->assertTrue(Suppressions::compare(['phpstan.neon' => self::FROZEN_IGNORE], ['phpstan.neon' => $changed])['fail']);
        $this->assertFalse(Suppressions::compare(['phpstan.neon' => self::FROZEN_IGNORE], ['phpstan.neon' => $removed])['fail']);
    }

    public function test_adding_or_changing_a_baseline_fails_and_deleting_one_passes(): void
    {
        $added = Suppressions::compare([], ['phpstan-baseline.neon' => "ignore:\n"]);
        $changed = Suppressions::compare(
            ['phpstan-baseline.neon' => "ignore:\n  - a\n"],
            ['phpstan-baseline.neon' => "ignore:\n  - b\n"],
        );
        $deleted = Suppressions::compare(['phpstan-baseline.neon' => "ignore:\n"], []);

        $this->assertTrue($added['fail']);
        $this->assertTrue($changed['fail']);
        $this->assertFalse($deleted['fail']);
    }

    public function test_deleted_tests_and_suspected_weakening_are_reported_without_failing(): void
    {
        $result = TestChanges::review(
            [
                'tests/Unit/OldTest.php' => "<?php\nfinal class OldTest { public function test_a(): void { \$this->assertSame(1, 1); } }\n",
                'tests/Unit/WeakTest.php' => "<?php\nfinal class WeakTest { public function test_a(): void { \$this->assertSame(1, 1); } }\n",
            ],
            [
                'tests/Unit/WeakTest.php' => "<?php\nfinal class WeakTest { public function test_a(): void { \$this->assertTrue(true); } }\n",
            ],
            [],
        );

        $this->assertFalse($result['fail']);
        $this->assertSame(['tests/Unit/OldTest.php'], $result['deleted']);
        $this->assertNotSame([], $result['suspected']);
    }

    public function test_an_approved_path_change_fails_only_when_that_path_is_configured(): void
    {
        $base = ['tests/Fixture/ApprovedTest.php' => "<?php\n"];
        $head = ['tests/Fixture/ApprovedTest.php' => "<?php\n// changed\n"];

        $this->assertFalse(TestChanges::review($base, $head, [])['fail']);
        $this->assertTrue(TestChanges::review($base, $head, ['tests/Fixture/ApprovedTest.php'])['fail']);
        $this->assertFalse(TestChanges::review(
            ['tests/Unit/ReadCapTest.php' => "<?php\n"],
            ['tests/Unit/ReadCapTest.php' => "<?php\n// edited\n"],
            [],
        )['fail']);
    }

    public function test_the_committed_approval_list_enrolls_no_production_test(): void
    {
        $paths = TestChanges::pathsFromFile((string) file_get_contents(dirname(__DIR__, 2).'/tools/Guardrails/approved-tests.txt'));

        $this->assertSame([], $paths);
    }

    public function test_changed_file_commands_use_explicit_paths_and_keep_the_full_gate_separate(): void
    {
        $pint = ChangedFiles::pintCommand(['app/A.php', 'tests/B.php']);
        $phpstan = ChangedFiles::phpstanCommand(['app/A.php']);

        $this->assertSame(['vendor/bin/pint', '--test', 'app/A.php', 'tests/B.php'], $pint);
        $this->assertNotContains('--dirty', $pint);
        $this->assertSame('vendor/bin/phpstan', $phpstan[0]);
        $this->assertContains('app/A.php', $phpstan);
        $this->assertSame(['app/A.php'], ChangedFiles::phpFiles([
            'app/A.php',
            'README.md',
            'vendor/x.php',
            'tools/Guardrails/fixtures/smells/fail/empty-catch.php',
        ]));
    }

    public function test_a_missing_new_direct_package_fails_and_an_outage_is_not_that_failure(): void
    {
        $base = '{"require":{"php":"~8.5.0"},"require-dev":{}}';
        $head = '{"require":{"php":"~8.5.0","example/missing":"^1.0"},"require-dev":{"ext-json":"*"}}';
        $this->assertSame(['example/missing'], Packages::newDirectPackages($base, $head));

        $missing = Packages::verify(['example/missing'], static fn (string $package): int => 404);
        $found = Packages::verify(['laravel/framework'], static fn (string $package): int => 200);
        $outage = Packages::verify(['laravel/framework'], static function (string $package): int {
            throw new \RuntimeException('connection timed out for '.$package);
        });

        $this->assertSame(1, $missing['exit']);
        $this->assertSame('missing', $missing['results'][0]['status']);
        $this->assertSame(0, $found['exit']);
        $this->assertSame('found', $found['results'][0]['status']);
        $this->assertSame(2, $outage['exit']);
        $this->assertSame('unavailable', $outage['results'][0]['status']);
    }

    public function test_pull_request_size_is_reported_with_no_threshold(): void
    {
        $summary = PullRequestSize::summarize([[10, 2], [3, 4]]);
        $cli = file_get_contents(dirname(__DIR__, 2).'/tools/Guardrails/pr-size.php');

        $this->assertSame(19, $summary['changed_lines']);
        $this->assertIsString($cli);
        $this->assertStringContainsString('No failure threshold is configured.', $cli);
        $this->assertStringContainsString('exit(0);', $cli);
    }

    public function test_semgrep_rejects_empty_catches_and_debug_calls_and_allows_rethrow(): void
    {
        $binary = $this->semgrepBinary();
        $fail = $this->semgrep($binary, dirname(__DIR__, 2).'/tools/Guardrails/fixtures/smells/fail');
        $pass = $this->semgrep($binary, dirname(__DIR__, 2).'/tools/Guardrails/fixtures/smells/pass');

        $this->assertNotSame(0, $fail['exit'], $fail['output']);
        $this->assertStringContainsString('quay-empty-catch', $fail['output']);
        $this->assertStringContainsString('quay-debug-call', $fail['output']);
        $this->assertSame(0, $pass['exit'], $pass['output']);
    }

    private function semgrepBinary(): string
    {
        foreach (['semgrep', dirname(__DIR__, 2).'/../../.local/bin/semgrep'] as $candidate) {
            if ($candidate === 'semgrep') {
                $process = new Process(['python3', '-m', 'semgrep', '--version']);
                $process->run();
                if ($process->isSuccessful()) {
                    return 'python3 -m semgrep';
                }

                continue;
            }
        }

        $home = getenv('HOME');
        if (is_string($home) && is_executable($home.'/.local/bin/semgrep')) {
            return $home.'/.local/bin/semgrep';
        }

        $this->fail('Semgrep is required to verify the PHP smell rules.');
    }

    /**
     * @return array{exit: int, output: string}
     */
    private function semgrep(string $binary, string $path): array
    {
        $command = $binary === 'python3 -m semgrep'
            ? ['python3', '-m', 'semgrep', 'scan', '--config', dirname(__DIR__, 2).'/.semgrep/ai-smells.yml', '--metrics=off', '--error', '--no-git-ignore', $path]
            : [$binary, 'scan', '--config', dirname(__DIR__, 2).'/.semgrep/ai-smells.yml', '--metrics=off', '--error', '--no-git-ignore', $path];
        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        return [
            'exit' => $process->getExitCode() ?? 1,
            'output' => $process->getOutput().$process->getErrorOutput(),
        ];
    }
}
