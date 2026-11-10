<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class Cli
{
    /**
     * @return list<string>
     */
    public static function arguments(): array
    {
        return Tokens::fromGlobals();
    }

    /**
     * @param  list<string>  $arguments
     */
    public static function base(array $arguments, int $index): string
    {
        $given = $arguments[$index] ?? '';
        if ($given !== '') {
            return $given;
        }

        $fromEnv = getenv('GUARD_BASE');
        if (is_string($fromEnv) && $fromEnv !== '') {
            return $fromEnv;
        }

        return 'origin/main';
    }
}
