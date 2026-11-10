<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Actions;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Delivery\Data\StopView;
use App\Modules\Delivery\Enums\StopStatus;
use App\Modules\Delivery\Exceptions\InvalidTransition;
use App\Modules\Delivery\Exceptions\StopNotFound;
use App\Modules\Delivery\Models\DeliveryStop;
use App\Modules\Inventory\Actions\ConsumeReservation;
use App\Modules\Inventory\Exceptions\ConcurrentModification;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;

final class TransitionStop
{
    public function __construct(
        private readonly ConsumeReservation $consumeReservation,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(string $stopId, StopStatus $next, Actor $actor): StopView
    {
        return DB::transaction(function () use ($stopId, $next, $actor): StopView {
            $stop = DeliveryStop::query()->select(DeliveryStop::readColumns())->whereKey($stopId)->lockForUpdate()->first();

            if ($stop === null) {
                throw new StopNotFound($stopId);
            }

            if ($stop->status === $next) {
                return $this->view($stop, true);
            }

            if (! $stop->status->canTransitionTo($next)) {
                throw new InvalidTransition($stop->status->value, $next->value);
            }

            if ($next === StopStatus::Delivered && is_string($stop->reservation_id) && $stop->reservation_id !== '') {
                $this->consumeReservation->execute($stop->reservation_id, $actor);
            }

            $updated = DeliveryStop::query()
                ->whereKey($stop->id)
                ->where('version', $stop->version)
                ->update([
                    'status' => $next->value,
                    'version' => $stop->version + 1,
                    'updated_at' => now(),
                ]);

            if ($updated !== 1) {
                throw new ConcurrentModification;
            }

            $from = $stop->status->value;
            $stop->status = $next;
            $stop->version++;

            $this->audit->execute(
                $actor,
                'delivery.status_changed',
                'delivery_stop',
                $stop->id,
                $stop->warehouse_code,
                ['from' => $from, 'to' => $next->value],
            );

            return $this->view($stop, false);
        }, 3);
    }

    private function view(DeliveryStop $stop, bool $replayed): StopView
    {
        return new StopView(
            id: $stop->id,
            reference: $stop->reference,
            reservationId: $stop->reservation_id,
            warehouseCode: $stop->warehouse_code,
            skuCode: $stop->sku_code,
            quantity: $stop->quantity,
            status: $stop->status->value,
            version: $stop->version,
            allowedTransitions: $stop->status->allowedNextValues(),
            replayed: $replayed,
        );
    }
}
