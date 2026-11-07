<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Exceptions;

use RuntimeException;

final class StopNotFound extends RuntimeException
{
    public function __construct(public readonly string $stopId)
    {
        parent::__construct('Delivery stop was not found.');
    }
}
