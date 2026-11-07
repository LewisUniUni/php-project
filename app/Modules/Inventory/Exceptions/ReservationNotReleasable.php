<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class ReservationNotReleasable extends RuntimeException
{
    public function __construct(public readonly string $status)
    {
        parent::__construct('Reservation cannot be released from status '.$status.'.');
    }
}
