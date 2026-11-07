<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Enums;

enum ReservationStatus: string
{
    case Held = 'held';
    case Released = 'released';
    case Consumed = 'consumed';
}
