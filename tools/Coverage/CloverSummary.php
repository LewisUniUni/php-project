<?php

declare(strict_types=1);

namespace Quay\Tools\Coverage;

use RuntimeException;

final class CloverSummary
{
    /**
     * Statement coverage for the files PHPUnit put in the Clover report.
     *
     * The denominator is every statement line in that report, including files
     * whose covered-statement count is zero. It is not restricted to files
     * that already have some coverage.
     *
     * @param  list<string>  $productionFiles
     * @return array{
     *     statement_count: int,
     *     covered_statement_count: int,
     *     statement_rate: float|null,
     *     files_in_report: int,
     *     zero_coverage_files: list<string>,
     *     missing_production_files: list<string>,
     *     denominator: string
     * }
     */
    public function summarize(string $cloverFile, array $productionFiles): array
    {
        $reportFiles = $this->files($cloverFile);
        $statements = 0;
        $covered = 0;
        $zero = [];
        $reported = [];

        foreach ($reportFiles as $name => $counts) {
            $reported[$this->normalize($name)] = true;
            $statements += $counts['statements'];
            $covered += $counts['covered'];

            if ($counts['statements'] > 0 && $counts['covered'] === 0) {
                $zero[] = $name;
            }
        }

        $missing = [];

        foreach ($productionFiles as $file) {
            if (! isset($reported[$this->normalize($file)])) {
                $missing[] = $file;
            }
        }

        sort($zero);
        sort($missing);

        return [
            'statement_count' => $statements,
            'covered_statement_count' => $covered,
            'statement_rate' => $statements === 0 ? null : $covered / $statements,
            'files_in_report' => count($reportFiles),
            'zero_coverage_files' => $zero,
            'missing_production_files' => $missing,
            'denominator' => 'Every statement line in the Clover report, including files with zero covered statements. phpunit.xml includes the app directory and sets includeUncoveredFiles to true, so an app file that no test executed stays in this denominator.',
        ];
    }

    /**
     * @return array<string, array{statements: int, covered: int}>
     */
    private function files(string $cloverFile): array
    {
        if (! is_file($cloverFile)) {
            throw new RuntimeException('Clover report not found: '.$cloverFile);
        }

        $xml = simplexml_load_file($cloverFile);

        if ($xml === false) {
            throw new RuntimeException('Clover report is not valid XML: '.$cloverFile);
        }

        $files = [];

        $nodes = $xml->xpath('//file');
        if (! is_array($nodes)) {
            $nodes = [];
        }

        foreach ($nodes as $file) {
            $name = (string) $file['name'];
            $statements = 0;
            $covered = 0;

            foreach ($file->line as $line) {
                if ((string) $line['type'] !== 'stmt') {
                    continue;
                }

                $statements++;

                if ((int) $line['count'] > 0) {
                    $covered++;
                }
            }

            $files[$name] = [
                'statements' => $statements,
                'covered' => $covered,
            ];
        }

        return $files;
    }

    private function normalize(string $path): string
    {
        $real = realpath($path);

        if ($real !== false) {
            return $real;
        }

        return str_replace('\\', '/', $path);
    }
}
