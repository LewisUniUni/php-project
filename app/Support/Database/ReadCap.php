<?php

declare(strict_types=1);

namespace App\Support\Database;

final class ReadCap
{
    public const int MAX_ROWS = 100;

    /**
     * @param  array<int|string, mixed>  $bindings
     */
    public static function assertSql(string $sql, array $bindings): void
    {
        SqlStatement::assert($sql, $bindings);
    }

    public static function assertFetchSize(int $limit): void
    {
        if ($limit < 1 || $limit > self::MAX_ROWS) {
            throw new ProhibitedRead('Application fetch size '.$limit.' is outside 1..'.self::MAX_ROWS.'.');
        }
    }

    /**
     * MongoDB collection reads do not pass through the SQL guard.
     * A count/sum/min/max/avg with no groups returns one result. The documents
     * scanned are not the result size. A grouped aggregate can return one
     * document per group and still needs a limit.
     *
     * @param  array<int|string, mixed>|null  $columns
     * @param  array<int|string, mixed>  $groups
     */
    public static function assertMongoFetch(mixed $limit, ?array $columns, array $groups, mixed $aggregate): void
    {
        if ($aggregate !== null && $groups === []) {
            return;
        }

        if ($columns === null || $columns === [] || in_array('*', $columns, true)) {
            throw new ProhibitedRead('MongoDB application read must name fields. An empty projection returns every field.');
        }

        if (! is_int($limit)) {
            throw new ProhibitedRead('MongoDB application read has no numeric limit of 1..'.self::MAX_ROWS.'. A dynamic or missing limit is not treated as safe.');
        }

        if ($limit < 1 || $limit > self::MAX_ROWS) {
            throw new ProhibitedRead('MongoDB application read limit '.$limit.' is outside 1..'.self::MAX_ROWS.'.');
        }
    }

    public static function originatesInApplication(): bool
    {
        $app = base_path('app').DIRECTORY_SEPARATOR;
        $guard = base_path('app/Support/Database').DIRECTORY_SEPARATOR;
        $provider = base_path('app/Providers/AppServiceProvider.php');

        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            $file = $frame['file'] ?? '';

            if ($file === '') {
                continue;
            }

            if (str_starts_with($file, $guard) || $file === $provider) {
                continue;
            }

            $normalized = str_replace('\\', '/', $file);

            if (str_contains($normalized, '/Illuminate/Database/')) {
                continue;
            }

            if (str_contains($normalized, '/mongodb/laravel-mongodb/src/Query/')) {
                continue;
            }

            if (str_contains($normalized, '/mongodb/laravel-mongodb/src/Eloquent/Builder.php')) {
                continue;
            }

            return str_starts_with($file, $app);
        }

        return false;
    }
}
