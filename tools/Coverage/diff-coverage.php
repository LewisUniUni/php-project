<?php

declare(strict_types=1);

require __DIR__.'/DiffCoverage.php';

use Quay\Tools\Coverage\DiffCoverage;

if ($argc < 2) {
    fwrite(STDERR, "Usage: diff-coverage.php <clover.xml> [summary.md]\n");
    exit(2);
}

$cloverFile = $argv[1];
$summaryFile = $argv[2] ?? 'storage/coverage/diff-coverage.md';
$diff = '';
$base = 'not measured';

if (getenv('COVERAGE_DIFF') !== false && is_file((string) getenv('COVERAGE_DIFF'))) {
    $diff = (string) file_get_contents((string) getenv('COVERAGE_DIFF'));
    $base = (string) getenv('COVERAGE_DIFF');
} else {
    $candidates = [];
    $explicit = getenv('COVERAGE_DIFF_BASE');

    if (is_string($explicit) && $explicit !== '') {
        $candidates[] = $explicit;
    }

    $githubBase = getenv('GITHUB_BASE_REF');

    if (is_string($githubBase) && $githubBase !== '') {
        $candidates[] = 'origin/'.$githubBase;
    }

    $candidates[] = 'origin/main';

    foreach ($candidates as $candidate) {
        $command = 'git diff --unified=0 '.escapeshellarg($candidate.'...HEAD').' -- app';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            continue;
        }

        $diff = implode("\n", $output);
        $base = $candidate;

        if (trim($diff) === '') {
            $workingTree = [];
            $workingTreeExit = 0;
            exec('git diff '.escapeshellarg($candidate).' -- app', $workingTree, $workingTreeExit);

            if ($workingTreeExit === 0 && trim(implode("\n", $workingTree)) !== '') {
                $diff = implode("\n", $workingTree);
                $base = $candidate.' working tree';
            }
        }

        break;
    }
}

$directory = dirname($summaryFile);

if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}

if ($base === 'not measured') {
    $markdown = "# Diff coverage\n\nNo diff base was available. Changed-line coverage was not measured. No numeric gate is configured.\n";
    file_put_contents($summaryFile, $markdown);
    fwrite(STDOUT, "diff coverage not measured\n");
    exit(0);
}

$result = (new DiffCoverage)->measure($cloverFile, $diff);
$rate = $result['statement_rate'] === null
    ? 'n/a'
    : number_format($result['statement_rate'] * 100, 2).'%';

$markdown = implode("\n", [
    '# Diff coverage',
    '',
    'Base: `'.$base.'`.',
    '',
    'Scope: statement lines in `app/` that this diff added or changed and that exist in the Clover report.',
    '',
    'No minimum rate is configured. The playbook left the changed-code floor blank.',
    '',
    '- Changed executable statements: '.$result['changed_executable_statements'],
    '- Covered changed executable statements: '.$result['covered_changed_executable_statements'],
    '- Statement rate: '.$rate,
    '- Threshold: none',
    '',
]);

file_put_contents($summaryFile, $markdown);
fwrite(STDOUT, $rate.' of changed executable statements ('.$result['covered_changed_executable_statements'].'/'.$result['changed_executable_statements'].")\n");
exit(0);
