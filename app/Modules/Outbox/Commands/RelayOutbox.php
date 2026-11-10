<?php

declare(strict_types=1);

namespace App\Modules\Outbox\Commands;

use App\Modules\Outbox\Enums\OutboxStatus;
use App\Modules\Outbox\Jobs\DeliverOutboxMessage;
use App\Modules\Outbox\Models\OutboxMessage;
use App\Support\Database\ReadCap;
use App\Support\Values\ConfiguredInt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class RelayOutbox extends Command
{
    protected $signature = 'outbox:relay';

    protected $description = 'Lease pending outbox messages and hand them to the queue';

    public function handle(): int
    {
        $ids = DB::transaction(function (): array {
            $batch = ConfiguredInt::get('operations.outbox_batch', 50);
            ReadCap::assertFetchSize($batch);

            $messages = OutboxMessage::query()->select(OutboxMessage::readColumns())
                ->where(function ($query): void {
                    $query->where('status', OutboxStatus::Pending)
                        ->orWhere(function ($query): void {
                            $query->where('status', OutboxStatus::Leased)
                                ->where('leased_until', '<=', now());
                        });
                })
                ->orderBy('created_at')
                ->limit($batch)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get();

            $leased = [];

            foreach ($messages as $message) {
                if ($message->attempts >= ConfiguredInt::get('operations.outbox_max_attempts', 10)) {
                    $message->status = OutboxStatus::Failed;
                    $message->last_error = 'relay attempts exhausted';
                    $message->save();

                    continue;
                }

                $message->status = OutboxStatus::Leased;
                $message->attempts++;
                $message->leased_until = now()->addSeconds(ConfiguredInt::get('operations.outbox_lease_seconds', 60));
                $message->save();
                $leased[] = $message->id;
            }

            return $leased;
        });

        foreach ($ids as $id) {
            try {
                DeliverOutboxMessage::dispatch($id);
            } catch (Throwable $exception) {
                OutboxMessage::query()->whereKey($id)->update([
                    'status' => OutboxStatus::Pending->value,
                    'leased_until' => null,
                    'last_error' => 'dispatch failed',
                ]);
                Log::warning('outbox_dispatch_failed', [
                    'message_id' => $id,
                    'error' => $exception::class,
                ]);
            }
        }

        $this->info('Leased '.count($ids).' outbox message(s).');

        return self::SUCCESS;
    }
}
