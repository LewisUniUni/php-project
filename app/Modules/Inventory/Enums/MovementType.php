<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum MovementType: string
{
    case Receipt = 'receipt';
    case ReservationHold = 'reservation_hold';
    case ReservationRelease = 'reservation_release';
    case ReservationConsume = 'reservation_consume';
}
