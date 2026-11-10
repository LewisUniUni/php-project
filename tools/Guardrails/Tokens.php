<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class Tokens
{
    /**
     * Command-line values are not HTTP input. Only a capture from this pattern is passed to a process or a path.
     *
     * @return list<string>
     */
    public static function fromGlobals(): array
    {
        if (! array_key_exists('argv', $GLOBALS) || ! is_array($GLOBALS['argv'])) {
            return [];
        }

        $values = [];
        foreach ($GLOBALS['argv'] as $argument) {
            if (! is_string($argument)) {
                continue;
            }

            $safe = self::keep($argument);
            if ($safe === null) {
                fwrite(STDERR, "Refusing an unsafe argument.\n");
                exit(2);
            }

            $values[] = $safe;
        }

        return $values;
    }

    public static function keep(string $value): ?string
    {
        if (preg_match('/\A([A-Za-z0-9._+:\/@~=-]{1,1024})\z/', $value, $matches) !== 1) {
            return null;
        }

        if (str_contains($matches[1], '..')) {
            return null;
        }

        return $matches[1];
    }
}
