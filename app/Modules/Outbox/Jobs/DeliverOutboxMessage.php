<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Jobs;

use App\Modules\Delivery\Actions\EnsurePlannedStop;
use App\Modules\Outbox\Enums\OutboxStatus;
use App\Modules\Outbox\Models\OutboxMessage;
use App\Support\Runtime\CorrelationContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class DeliverOutboxMessage implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 30;

    public function __construct(public readonly string $messageId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [1, 5, 15, 60];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    public function handle(EnsurePlannedStop $stops, CorrelationContext $context): void
    {
        $jobId = $this->job?->getJobId();
        $context->replace(
            requestId: null,
            traceId: bin2hex(random_bytes(16)),
            spanId: bin2hex(random_bytes(8)),
            jobId: is_string($jobId) ? $jobId : 'inline',
        );
        Log::withContext($context->logContext());

        $message = OutboxMessage::query()->select(OutboxMessage::readColumns())->find($this->messageId);

        if ($message === null || $message->status === OutboxStatus::Processed) {
            return;
        }

        DB::transaction(function () use ($message, $stops): void {
            $inserted = DB::table('processed_messages')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'message_id' => $message->id,
                'consumer' => self::class,
                'processed_at' => now(),
            ]);

            if ($inserted === 0) {
                $this->markProcessed($message);

                return;
            }

            if ($message->type === 'inventory.reservation_held') {
                $stops->execute($message->payload);
            }

            $this->markProcessed($message);
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('outbox_message_failed', [
            'message_id' => $this->messageId,
            'error' => $exception::class,
        ]);
    }

    private function markProcessed(OutboxMessage $message): void
    {
        $message->status = OutboxStatus::Processed;
        $message->processed_at = now();
        $message->last_error = null;
        $message->save();
    }
}
