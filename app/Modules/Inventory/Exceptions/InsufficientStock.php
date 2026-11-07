<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class InsufficientStock extends RuntimeException
{
    public function __construct(
        public readonly string $skuCode,
        public readonly string $locationCode,
        public readonly int $available,
        public readonly int $requested,
    ) {
        parent::__construct('Insufficient stock.');
    }
}
