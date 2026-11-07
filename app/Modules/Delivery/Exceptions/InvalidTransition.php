<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Exceptions;

use RuntimeException;

final class InvalidTransition extends RuntimeException
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {
        parent::__construct('Cannot move a delivery stop from '.$from.' to '.$to.'.');
    }
}
