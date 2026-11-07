<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Enums;

enum StopStatus: string
{
    case Planned = 'planned';
    case Dispatched = 'dispatched';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Planned => [self::Dispatched, self::Cancelled],
            self::Dispatched => [self::InTransit, self::Cancelled],
            self::InTransit => [self::Delivered, self::Failed],
            self::Delivered, self::Failed, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedNext(), true);
    }

    /**
     * @return list<string>
     */
    public function allowedNextValues(): array
    {
        return array_map(static fn (self $status): string => $status->value, $this->allowedNext());
    }
}
