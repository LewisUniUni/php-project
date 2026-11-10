<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class Packages
{
    /**
     * @return list<string>
     */
    public static function newDirectPackages(string $baseJson, string $headJson): array
    {
        $before = self::direct($baseJson);
        $after = self::direct($headJson);
        $added = [];

        foreach ($after as $name) {
            if (! in_array($name, $before, true)) {
                $added[] = $name;
            }
        }

        sort($added);

        return $added;
    }

    /**
     * Existence is not trustworthiness. An outage is not a missing package.
     *
     * @param  list<string>  $packages
     * @param  callable(string): int  $status
     * @return array{exit: int, results: list<array{package: string, status: string}>}
     */
    public static function verify(array $packages, callable $status): array
    {
        $results = [];
        $exit = 0;

        foreach ($packages as $package) {
            try {
                $code = $status($package);
            } catch (\Throwable) {
                $results[] = ['package' => $package, 'status' => 'unavailable'];
                $exit = max($exit, 2);

                continue;
            }

            if ($code === 200) {
                $results[] = ['package' => $package, 'status' => 'found'];

                continue;
            }

            if ($code === 404) {
                $results[] = ['package' => $package, 'status' => 'missing'];
                $exit = max($exit, 1);

                continue;
            }

            $results[] = ['package' => $package, 'status' => 'unavailable'];
            $exit = max($exit, 2);
        }

        return ['exit' => $exit, 'results' => $results];
    }

    /**
     * @return list<string>
     */
    private static function direct(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $names = [];

        foreach (['require', 'require-dev'] as $section) {
            $items = $decoded[$section] ?? [];
            if (! is_array($items)) {
                continue;
            }

            foreach (array_keys($items) as $name) {
                if (! is_string($name) || $name === 'php' || str_starts_with($name, 'ext-')) {
                    continue;
                }

                $names[] = $name;
            }
        }

        return $names;
    }
}
