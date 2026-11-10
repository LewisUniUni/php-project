<?php

declare(strict_types=1);

namespace App\Support\Database;

final class SqlScan
{
    public static function normalize(string $sql): string
    {
        $collapsed = preg_replace('/\s+/', ' ', trim($sql));

        return is_string($collapsed) ? strtolower($collapsed) : strtolower(trim($sql));
    }

    public static function stripLeadingComments(string $sql): string
    {
        $rest = ltrim($sql);

        while (str_starts_with($rest, '/*')) {
            $end = strpos($rest, '*/');
            if ($end === false) {
                return $rest;
            }

            $rest = ltrim(substr($rest, $end + 2));
        }

        return $rest;
    }

    public static function from(string $sql): ?int
    {
        return self::keyword($sql, 'from');
    }

    public static function groupBy(string $sql): ?int
    {
        return self::keyword($sql, 'group by');
    }

    public static function limit(string $sql): ?int
    {
        return self::keyword($sql, 'limit');
    }

    public static function placeholdersBefore(string $sql, int $position): int
    {
        $count = 0;
        $length = min($position, strlen($sql));

        for ($i = 0; $i < $length; $i++) {
            if ($sql[$i] === '?') {
                $count++;
            }
        }

        return $count;
    }

    public static function keyword(string $sql, string $keyword): ?int
    {
        $length = strlen($sql);
        $needle = strlen($keyword);
        $depth = 0;

        for ($i = 0; $i < $length; $i++) {
            $character = $sql[$i];

            if ($character === '(') {
                $depth++;

                continue;
            }

            if ($character === ')') {
                $depth = max(0, $depth - 1);

                continue;
            }

            if ($depth !== 0 || $i + $needle > $length || substr($sql, $i, $needle) !== $keyword) {
                continue;
            }

            $before = $i === 0 ? ' ' : $sql[$i - 1];
            $afterIndex = $i + $needle;
            $after = $afterIndex >= $length ? ' ' : $sql[$afterIndex];

            if (! self::isBoundary($before) || ! self::isBoundary($after)) {
                continue;
            }

            return $i;
        }

        return null;
    }

    private static function isBoundary(string $character): bool
    {
        return preg_match('/\s/', $character) === 1 || $character === '(';
    }
}
