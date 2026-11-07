<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Actions;

use App\Modules\Outbox\Enums\OutboxStatus;
use App\Modules\Outbox\Models\OutboxMessage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RecordOutboxMessage
{
    /**
     * Write the message in the caller's transaction. A queue nudge runs after
     * commit; the scheduler relay remains the retry path if Redis is down.
     *
     * @param  array<string, mixed>  $payload
     */
    public function execute(string $type, array $payload): string
    {
        $message = OutboxMessage::query()->create([
            'type' => $type,
            'payload' => $payload,
            'status' => OutboxStatus::Pending,
            'attempts' => 0,
        ]);

        if (config('operations.outbox_nudge') === true) {
            DB::afterCommit(function () use ($message): void {
                try {
                    Artisan::queue('outbox:relay');
                } catch (Throwable $exception) {
                    Log::warning('outbox_nudge_failed', [
                        'message_id' => $message->id,
                        'error' => $exception::class,
                    ]);
                }
            });
        }

        return $message->id;
    }
}
