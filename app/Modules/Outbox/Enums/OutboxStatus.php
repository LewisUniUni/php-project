<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Enums;

enum OutboxStatus: string
{
    case Pending = 'pending';
    case Leased = 'leased';
    case Processed = 'processed';
    case Failed = 'failed';
}
