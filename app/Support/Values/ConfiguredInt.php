<?php

declare(strict_types=1);

namespace App\Support\Values;

use UnexpectedValueException;

final class ConfiguredInt
{
    public static function get(string $key, int $default): int
    {
        return self::parse(config($key, $default), $key);
    }

    public static function parse(mixed $value, string $key): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/\A-?\d+\z/', $value) === 1) {
            return intval($value, 10);
        }

        throw new UnexpectedValueException($key.' must be an integer.');
    }
}
