<?php

declare(strict_types=1);

namespace Quay\Tools\Guardrails;

final class Text
{
    /**
     * @return list<string>
     */
    public static function lines(string $source): array
    {
        $lines = preg_split('/\R/', $source);
        if (! is_array($lines)) {
            return [];
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    public static function fields(string $line): array
    {
        $parts = preg_split('/\s+/', $line);
        if (! is_array($parts)) {
            return [];
        }

        $result = [];
        foreach ($parts as $part) {
            if ($part !== '') {
                $result[] = $part;
            }
        }

        return $result;
    }
}
