<?php

declare(strict_types=1);

namespace Quay\Tools\Coverage;

use RuntimeException;

final class DiffCoverage
{
    /**
     * Coverage of changed statement lines. There is no minimum rate.
     *
     * @return array{
     *     changed_executable_statements: int,
     *     covered_changed_executable_statements: int,
     *     statement_rate: float|null
     * }
     */
    public function measure(string $cloverFile, string $diff): array
    {
        $lines = $this->statementLines($cloverFile);
        $changed = $this->changedLines($diff);
        $executable = 0;
        $covered = 0;

        foreach ($changed as $file => $numbers) {
            foreach (array_keys($numbers) as $number) {
                $count = $this->lookup($lines, $file, $number);

                if ($count === null) {
                    continue;
                }

                $executable++;

                if ($count > 0) {
                    $covered++;
                }
            }
        }

        return [
            'changed_executable_statements' => $executable,
            'covered_changed_executable_statements' => $covered,
            'statement_rate' => $executable === 0 ? null : $covered / $executable,
        ];
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function statementLines(string $cloverFile): array
    {
        if (! is_file($cloverFile)) {
            throw new RuntimeException('Clover report not found: '.$cloverFile);
        }

        $xml = simplexml_load_file($cloverFile);

        if ($xml === false) {
            throw new RuntimeException('Clover report is not valid XML: '.$cloverFile);
        }

        $lines = [];

        foreach ($xml->xpath('//file') ?: [] as $file) {
            $name = str_replace('\\', '/', (string) $file['name']);

            foreach ($file->line as $line) {
                if ((string) $line['type'] !== 'stmt') {
                    continue;
                }

                $lines[$name][(int) $line['num']] = (int) $line['count'];
            }
        }

        return $lines;
    }

    /**
     * @return array<string, array<int, true>>
     */
    private function changedLines(string $diff): array
    {
        $changed = [];
        $file = null;
        $line = 0;

        foreach (preg_split("/\r\n|\n|\r/", $diff) ?: [] as $row) {
            if (str_starts_with($row, '+++ b/')) {
                $file = substr($row, 6);

                continue;
            }

            if (preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,\d+)? @@/', $row, $matches) === 1) {
                $line = (int) $matches[1];

                continue;
            }

            if ($file === null || $file === '/dev/null' || ! str_starts_with($file, 'app/')) {
                continue;
            }

            if (str_starts_with($row, '+')) {
                $changed[$file][$line] = true;
                $line++;

                continue;
            }

            if (str_starts_with($row, '-')) {
                continue;
            }

            if (str_starts_with($row, '\\')) {
                continue;
            }

            $line++;
        }

        return $changed;
    }

    /**
     * @param  array<string, array<int, int>>  $lines
     */
    private function lookup(array $lines, string $relativeFile, int $number): ?int
    {
        foreach ($lines as $reported => $counts) {
            if ($reported === $relativeFile || str_ends_with($reported, '/'.$relativeFile)) {
                return $counts[$number] ?? null;
            }
        }

        return null;
    }
}
