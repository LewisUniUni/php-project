<?php

declare(strict_types=1);

namespace App\Support\Database;

final class SqlStatement
{
    /**
     * @param  array<int|string, mixed>  $bindings
     */
    public static function assert(string $sql, array $bindings): void
    {
        $statement = SqlScan::stripLeadingComments(SqlScan::normalize($sql));

        if (! self::isRead($statement)) {
            return;
        }

        if (self::isMetadata($statement)) {
            throw new ProhibitedRead('Application metadata reads (SHOW, DESCRIBE, EXPLAIN) are not capped automatically. Name the result limit explicitly or do not run them from application code. SQL: '.$statement);
        }

        if (self::hasBareStar($statement)) {
            throw new ProhibitedRead('Application read must name columns. SELECT * is prohibited. SQL: '.$statement);
        }

        if (self::isSingleRowConstant($statement) || self::isSingleRowAggregate($statement)) {
            return;
        }

        $limit = self::outerLimit($statement, $bindings);

        if ($limit === null) {
            throw new ProhibitedRead('Application read has no explicit LIMIT of 1..'.ReadCap::MAX_ROWS.'. The cap is not applied by rewriting the query. SQL: '.$statement);
        }

        if ($limit < 1 || $limit > ReadCap::MAX_ROWS) {
            throw new ProhibitedRead('Application read LIMIT '.$limit.' is outside 1..'.ReadCap::MAX_ROWS.'. SQL: '.$statement);
        }
    }

    private static function isRead(string $sql): bool
    {
        return preg_match('/^(select|with|show|describe|desc|explain)\b/', $sql) === 1;
    }

    private static function isMetadata(string $sql): bool
    {
        return preg_match('/^(show|describe|desc|explain)\b/', $sql) === 1;
    }

    private static function isSingleRowConstant(string $sql): bool
    {
        return SqlScan::from($sql) === null && SqlScan::groupBy($sql) === null;
    }

    private static function isSingleRowAggregate(string $sql): bool
    {
        if (SqlScan::groupBy($sql) !== null) {
            return false;
        }

        $list = self::selectList($sql);

        if ($list === null || $list === '') {
            return false;
        }

        foreach (self::items($list) as $expression) {
            $withoutAlias = preg_replace('/\s+as\s+.+$/', '', $expression) ?? $expression;

            if (preg_match('/^(count|sum|avg|min|max)\s*\(.*\)$/', trim($withoutAlias)) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private static function items(string $list): array
    {
        $depth = 0;
        $item = '';
        $items = [];
        $length = strlen($list);

        for ($i = 0; $i < $length; $i++) {
            $character = $list[$i];

            if ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth = max(0, $depth - 1);
            }

            if ($character === ',' && $depth === 0) {
                $items[] = trim($item);
                $item = '';

                continue;
            }

            $item .= $character;
        }

        $items[] = trim($item);

        return $items;
    }

    private static function hasBareStar(string $sql): bool
    {
        $list = self::selectList($sql);

        if ($list === null) {
            return false;
        }

        $depth = 0;
        $length = strlen($list);

        for ($i = 0; $i < $length; $i++) {
            $character = $list[$i];

            if ($character === '(') {
                $depth++;
            } elseif ($character === ')') {
                $depth = max(0, $depth - 1);
            } elseif ($character === '*' && $depth === 0) {
                return true;
            }
        }

        return false;
    }

    private static function selectList(string $sql): ?string
    {
        if (preg_match('/^with\b/', $sql) === 1) {
            return null;
        }

        if (preg_match('/^select\s+/', $sql) !== 1) {
            return null;
        }

        $from = SqlScan::from($sql);
        $start = strlen('select ');

        if ($from === null) {
            $limit = SqlScan::limit($sql);

            return trim($limit === null ? substr($sql, $start) : substr($sql, $start, $limit - $start));
        }

        return trim(substr($sql, $start, $from - $start));
    }

    /**
     * @param  array<int|string, mixed>  $bindings
     */
    private static function outerLimit(string $sql, array $bindings): ?int
    {
        $position = SqlScan::limit($sql);

        if ($position === null) {
            return null;
        }

        $clause = trim(substr($sql, $position + strlen('limit')));
        $clause = self::withoutLockClause($clause);

        if (preg_match('/^(\d+)\s*,\s*(\d+)$/', $clause, $matches) === 1) {
            return (int) $matches[2];
        }

        if (preg_match('/^(\d+)(?:\s+offset\s+\d+)?$/', $clause, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/^\?(?:\s+offset\s+\?)?$/', $clause) !== 1) {
            throw new ProhibitedRead('Application read LIMIT could not be proven to be 1..'.ReadCap::MAX_ROWS.'. SQL: '.$sql);
        }

        return self::boundLimit($sql, $bindings, $position);
    }

    private static function withoutLockClause(string $clause): string
    {
        $clause = preg_replace('/\s+for\s+update\b.*$/', '', $clause) ?? $clause;
        $clause = preg_replace('/\s+lock\s+in\s+share\s+mode\b.*$/', '', $clause) ?? $clause;

        return trim($clause);
    }

    /**
     * @param  array<int|string, mixed>  $bindings
     */
    private static function boundLimit(string $sql, array $bindings, int $position): int
    {
        $value = $bindings[SqlScan::placeholdersBefore($sql, $position)] ?? null;

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^\d+$/', $value) === 1) {
            return (int) $value;
        }

        throw new ProhibitedRead('Application read LIMIT is not a visible integer. Dynamic limits are not treated as safe. SQL: '.$sql);
    }
}
