<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArchitectureHygieneTest extends TestCase
{
    public function test_application_code_does_not_dump_or_die(): void
    {
        $pattern = '/\b(?:dd|dump|var_dump|ray|ddd)\s*\(/';
        $failures = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app', \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            if (is_string($source) && preg_match($pattern, $source) === 1) {
                $failures[] = $file->getPathname();
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_concrete_controllers_are_final(): void
    {
        $failures = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/Http/Controllers', \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());

            if (! is_string($source) || ! str_contains($source, 'class ')) {
                continue;
            }

            if (str_contains($source, 'abstract class ')) {
                continue;
            }

            if (preg_match('/\bfinal\s+class\s+/', $source) !== 1) {
                $failures[] = $file->getPathname();
            }
        }

        $this->assertSame([], $failures);
    }

    public function test_the_stderr_log_channel_is_defined_once_and_redacts(): void
    {
        $source = file_get_contents('config/logging.php');
        $this->assertIsString($source);
        $this->assertSame(1, preg_match_all("/^        'stderr' => \\[$/m", $source));
        $this->assertStringContainsString('RedactSecretsProcessor::class', $source);
    }
}
