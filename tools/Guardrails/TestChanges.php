<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class TestChanges
{
    /**
     * @param  array<string, string|null>  $base
     * @param  array<string, string|null>  $head
     * @param  list<string>  $approvedPaths
     * @return array{fail: bool, deleted: list<string>, suspected: list<string>, approved: list<string>}
     */
    public static function review(array $base, array $head, array $approvedPaths): array
    {
        $approved = array_fill_keys(self::normalizeList($approvedPaths), true);
        $deleted = [];
        $suspected = [];
        $blocked = [];
        $paths = array_unique([...array_keys($base), ...array_keys($head)]);
        sort($paths);

        foreach ($paths as $path) {
            if (! self::isTest($path)) {
                continue;
            }

            $before = $base[$path] ?? null;
            $after = $head[$path] ?? null;
            $key = self::normalize($path);

            if (is_string($before) && ! is_string($after)) {
                $deleted[] = $path;
            } elseif (is_string($before) && $before !== $after) {
                foreach (self::removedAssertionLines($before, $after) as $line) {
                    $suspected[] = $path.': '.$line;
                }
            }

            if (isset($approved[$key]) && $before !== $after) {
                $blocked[] = $path;
            }
        }

        return [
            'fail' => $blocked !== [],
            'deleted' => $deleted,
            'suspected' => $suspected,
            'approved' => $blocked,
        ];
    }

    /**
     * @return list<string>
     */
    public static function pathsFromFile(string $contents): array
    {
        $paths = [];

        foreach (Text::lines($contents) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $paths[] = self::normalize($trimmed);
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private static function removedAssertionLines(string $before, string $after): array
    {
        $afterLines = array_count_values(self::assertionLines($after));
        $removed = [];

        foreach (self::assertionLines($before) as $line) {
            $remaining = $afterLines[$line] ?? 0;
            if ($remaining > 0) {
                $afterLines[$line] = $remaining - 1;

                continue;
            }

            $removed[] = $line;
        }

        return $removed;
    }

    /**
     * @return list<string>
     */
    private static function assertionLines(string $source): array
    {
        $lines = [];

        foreach (Text::lines($source) as $line) {
            $trimmed = trim($line);
            if (preg_match('/\b(assert\w+|expectException(?:Message)?|fail)\s*\(/', $trimmed) === 1) {
                $lines[] = $trimmed;
            }
        }

        return $lines;
    }

    private static function isTest(string $path): bool
    {
        $name = str_replace('\\', '/', $path);

        return str_contains($name, 'tests/') && str_ends_with($name, 'Test.php');
    }

    /**
     * @param  list<string>  $paths
     * @return list<string>
     */
    private static function normalizeList(array $paths): array
    {
        $normalized = [];

        foreach ($paths as $path) {
            $normalized[] = self::normalize($path);
        }

        return $normalized;
    }

    private static function normalize(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), './');
    }
}
