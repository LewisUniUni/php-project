<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class Suppressions
{
    /**
     * @param  array<string, string|null>  $base
     * @param  array<string, string|null>  $head
     * @return array{fail: bool, added: list<string>, removed: list<string>}
     */
    public static function compare(array $base, array $head): array
    {
        $added = [];
        $removed = [];
        $paths = array_unique([...array_keys($base), ...array_keys($head)]);
        sort($paths);

        foreach ($paths as $path) {
            $change = self::reviewPath($path, $base[$path] ?? null, $head[$path] ?? null);
            foreach ($change['added'] as $item) {
                $added[] = $item;
            }
            foreach ($change['removed'] as $item) {
                $removed[] = $item;
            }
        }

        return [
            'fail' => $added !== [],
            'added' => $added,
            'removed' => $removed,
        ];
    }

    /**
     * @return array{added: list<string>, removed: list<string>}
     */
    private static function reviewPath(string $path, ?string $before, ?string $after): array
    {
        if (self::isBaseline($path)) {
            return self::baselineChange($path, $before, $after);
        }

        $added = [];
        $removed = [];

        if (str_ends_with($path, 'phpstan.neon')) {
            self::record(
                $added,
                $removed,
                $path.' ignore ',
                self::entryDelta(self::ignoreEntries($before ?? ''), self::ignoreEntries($after ?? '')),
            );
        }

        self::record(
            $added,
            $removed,
            '',
            self::entryDelta(self::comments($path, $before ?? ''), self::comments($path, $after ?? '')),
        );

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * @return array{added: list<string>, removed: list<string>}
     */
    private static function baselineChange(string $path, ?string $before, ?string $after): array
    {
        if ($after !== null && $before === null) {
            return ['added' => [$path.' was added'], 'removed' => []];
        }

        if ($before !== null && $after !== null && $before !== $after) {
            return ['added' => [$path.' changed'], 'removed' => []];
        }

        if ($before !== null && $after === null) {
            return ['added' => [], 'removed' => [$path.' was removed']];
        }

        return ['added' => [], 'removed' => []];
    }

    /**
     * @param  list<string>  $added
     * @param  list<string>  $removed
     * @param  array{added: list<string>, removed: list<string>}  $delta
     */
    private static function record(array &$added, array &$removed, string $prefix, array $delta): void
    {
        foreach ($delta['added'] as $item) {
            $added[] = $prefix.$item;
        }

        foreach ($delta['removed'] as $item) {
            $removed[] = $prefix.$item;
        }
    }

    /**
     * @param  list<string>  $before
     * @param  list<string>  $after
     * @return array{added: list<string>, removed: list<string>}
     */
    private static function entryDelta(array $before, array $after): array
    {
        $beforeCounts = array_count_values($before);
        $afterCounts = array_count_values($after);
        $added = [];
        $removed = [];

        foreach ($afterCounts as $item => $count) {
            $extra = $count - ($beforeCounts[$item] ?? 0);
            for ($i = 0; $i < $extra; $i++) {
                $added[] = $item;
            }
        }

        foreach ($beforeCounts as $item => $count) {
            $missing = $count - ($afterCounts[$item] ?? 0);
            for ($i = 0; $i < $missing; $i++) {
                $removed[] = $item;
            }
        }

        return ['added' => $added, 'removed' => $removed];
    }

    /**
     * @return list<string>
     */
    private static function comments(string $path, string $source): array
    {
        $found = [];
        $lines = Text::lines($source);

        foreach ($lines as $index => $line) {
            $code = preg_replace('/\'(?:\\\\.|[^\'\\\\])*\'|"(?:\\\\.|[^"\\\\])*"/', '', $line);
            if (! is_string($code)) {
                $code = $line;
            }

            if (preg_match('/@(?:phpstan-ignore(?:-next-line)?|psalm-suppress)\b/', $code) !== 1) {
                continue;
            }

            $found[] = $path.':'.($index + 1).':'.trim($line);
        }

        return $found;
    }

    /**
     * @return list<string>
     */
    public static function ignoreEntries(string $neon): array
    {
        $lines = Text::lines($neon);
        $capturing = false;
        $indent = 0;
        $block = [];

        foreach ($lines as $line) {
            if (! $capturing) {
                if (preg_match('/^(\s*)ignoreErrors:\s*(.*)$/', $line, $matches) === 1) {
                    $capturing = true;
                    $indent = strlen($matches[1]);
                    if (trim($matches[2]) !== '') {
                        $block[] = trim($matches[2]);
                    }
                }

                continue;
            }

            if (trim($line) === '') {
                continue;
            }

            preg_match('/^(\s*)/', $line, $space);
            if (strlen($space[1] ?? '') <= $indent) {
                break;
            }

            $block[] = $line;
        }

        $entries = [];
        $current = [];

        $flush = static function () use (&$entries, &$current): void {
            $text = trim(implode("\n", $current));
            $text = preg_replace('/^\-\s*/', '', $text) ?? $text;
            $text = trim($text);
            if ($text !== '') {
                $entries[] = preg_replace('/\s+/', ' ', $text) ?? $text;
            }
            $current = [];
        };

        foreach ($block as $line) {
            if (preg_match('/^\s*-\s*(.*)$/', $line, $matches) === 1) {
                if ($current !== []) {
                    $flush();
                }
                if (trim($matches[1]) !== '') {
                    $current[] = trim($matches[1]);
                }

                continue;
            }

            $trimmed = trim($line);
            if ($trimmed !== '' && ! str_starts_with($trimmed, '#')) {
                $current[] = $trimmed;
            }
        }

        if ($current !== []) {
            $flush();
        }

        return $entries;
    }

    private static function isBaseline(string $path): bool
    {
        $name = str_replace('\\', '/', $path);

        return str_ends_with($name, 'phpstan-baseline.neon')
            || str_ends_with($name, 'psalm-baseline.xml')
            || str_contains($name, '/baseline.neon')
            || str_contains($name, '/baseline.xml');
    }
}
