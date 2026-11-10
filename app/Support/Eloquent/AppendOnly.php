<?php

declare(strict_types=1);

namespace App\Support\Eloquent;

use LogicException;

trait AppendOnly
{
    public function update(array $attributes = [], array $options = [])
    {
        // The signature matches Eloquent. The arguments are not applied.
        unset($attributes, $options);

        throw new LogicException(static::class.' records are append-only.');
    }

    public function delete()
    {
        throw new LogicException(static::class.' records are append-only.');
    }
}
