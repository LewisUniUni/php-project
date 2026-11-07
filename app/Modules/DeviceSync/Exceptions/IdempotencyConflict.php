<?php

declare(strict_types=1);

namespace App\Modules\DeviceSync\Exceptions;

use RuntimeException;

final class IdempotencyConflict extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('This Idempotency-Key was already used with a different request body.');
    }
}
