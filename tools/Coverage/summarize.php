<?php

declare(strict_types=1);

require __DIR__.'/CloverSummary.php';

use Quay\Tools\Coverage\CloverSummary;

$arguments = [];
if (isset($_SERVER['argv']) && is_array($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $argument) {
        if (is_string($argument)) {
            $arguments[] = $argument;
        }
    }
}

if (count($arguments) < 3) {
    fwrite(STDERR, "Usage: summarize.php <clover.xml> <summary.md> [production-dir]\n");
    exit(2);
}

$cloverFile = $arguments[1];
$summaryFile = $arguments[2];
$productionDir = $arguments[3] ?? 'app';
$productionFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($productionDir, FilesystemIterator::SKIP_DOTS));

foreach ($iterator as $file) {
    if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $productionFiles[] = $file->getPathname();
}

sort($productionFiles);

$summary = (new CloverSummary)->summarize($cloverFile, $productionFiles);
$rate = $summary['statement_rate'] === null
    ? 'n/a'
    : number_format($summary['statement_rate'] * 100, 2).'%';

$lines = [
    '# Coverage summary',
    '',
    'Production include scope: `'.$productionDir.'`.',
    '',
    $summary['denominator'],
    '',
    '- Statements: '.$summary['statement_count'],
    '- Covered statements: '.$summary['covered_statement_count'],
    '- Statement rate: '.$rate,
    '- Files in the Clover report: '.$summary['files_in_report'],
    '- Production PHP files missing from the report: '.count($summary['missing_production_files']),
    '- Files with statements and zero covered statements: '.count($summary['zero_coverage_files']),
    '',
];

if ($summary['missing_production_files'] !== []) {
    $lines[] = '## Missing production files';
    $lines[] = '';

    foreach ($summary['missing_production_files'] as $file) {
        $lines[] = '- `'.$file.'`';
    }

    $lines[] = '';
}

if ($summary['zero_coverage_files'] !== []) {
    $lines[] = '## Zero-coverage files';
    $lines[] = '';

    foreach ($summary['zero_coverage_files'] as $file) {
        $lines[] = '- `'.$file.'`';
    }

    $lines[] = '';
}

$directory = dirname($summaryFile);

if (! is_dir($directory)) {
    mkdir($directory, 0775, true);
}

file_put_contents($summaryFile, implode("\n", $lines));
fwrite(STDOUT, $rate.' statements ('.$summary['covered_statement_count'].'/'.$summary['statement_count'].') written to '.$summaryFile."\n");

exit($summary['missing_production_files'] === [] ? 0 : 1);
