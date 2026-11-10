<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class RawSqlPolicyTest extends TestCase
{
    public function test_application_code_does_not_call_raw_sql_apis(): void
    {
        $pattern = '/\b(?:DB::raw|DB::select|DB::statement|DB::unprepared|DB::affectingStatement|selectRaw|whereRaw|orWhereRaw|orderByRaw|groupByRaw|havingRaw|fromRaw)\s*\(/';
        $failures = [];

        foreach ($this->phpFiles('app') as $file) {
            $source = file_get_contents($file);

            if (! is_string($source)) {
                continue;
            }

            if (preg_match($pattern, $source) === 1) {
                $failures[] = $file;
            }
        }

        $this->assertSame([], $failures, 'Raw SQL calls are not checked by phpstan-dba in this Eloquent codebase. Keep them out of app/.');
    }

    public function test_sql_files_do_not_select_every_column_or_write_without_a_where(): void
    {
        $failures = [];

        foreach ($this->sqlFiles() as $file) {
            $source = file_get_contents($file);

            if (! is_string($source)) {
                continue;
            }

            if (preg_match('/\bselect\s+\*/i', $source) === 1) {
                $failures[] = $file.' contains SELECT *';
            }

            if (preg_match('/^\s*(?:update|delete)\b/im', $source) === 1 && preg_match('/\bwhere\b/i', $source) !== 1) {
                $failures[] = $file.' contains an UPDATE or DELETE statement without WHERE';
            }
        }

        $this->assertSame([], $failures);
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $directory): array
    {
        return $this->files($directory, 'php');
    }

    /**
     * @return list<string>
     */
    private function sqlFiles(): array
    {
        return array_merge($this->files('database', 'sql'), $this->files('docker', 'sql'));
    }

    /**
     * @return list<string>
     */
    private function files(string $directory, string $extension): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === $extension) {
                $paths[] = $file->getPathname();
            }
        }

        sort($paths);

        return $paths;
    }
}
