<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Actions;

use App\Modules\Outbox\Enums\OutboxStatus;
use App\Modules\Outbox\Models\OutboxMessage;

final class CountPendingMessages
{
    public function execute(): int
    {
        return OutboxMessage::query()->where('status', OutboxStatus::Pending)->count();
    }
}
