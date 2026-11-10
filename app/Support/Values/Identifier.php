<?php

declare(strict_types=1);

namespace App\Support\Values;

use UnexpectedValueException;

final class Identifier
{
    public static function string(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        throw new UnexpectedValueException('An identifier must be an integer or a non-empty string.');
    }
}
