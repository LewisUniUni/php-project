<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class ReservationNotFound extends RuntimeException
{
    public function __construct(public readonly string $reservationId)
    {
        parent::__construct('Reservation was not found.');
    }
}
