<?php

declare(strict_types=1);

namespace App\Support\Values;

use UnexpectedValueException;

final class ConfiguredString
{
    public static function get(string $key, string $default): string
    {
        return self::parse(config($key, $default), $key);
    }

    public static function parse(mixed $value, string $key): string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        throw new UnexpectedValueException($key.' must be a non-empty string.');
    }
}
