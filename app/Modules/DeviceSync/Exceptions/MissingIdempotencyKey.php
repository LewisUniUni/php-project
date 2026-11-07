<?php

declare(strict_types=1);

namespace App\Modules\DeviceSync\Exceptions;

use RuntimeException;

final class MissingIdempotencyKey extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('POST requests require an Idempotency-Key header containing a UUID.');
    }
}
