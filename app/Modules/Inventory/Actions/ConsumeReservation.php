<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Inventory\BalanceWriter;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Enums\ReservationStatus;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Exceptions\ReservationNotFound;
use App\Modules\Inventory\Models\Reservation;
use App\Modules\Inventory\Models\StockMovement;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;

final class ConsumeReservation
{
    public function __construct(
        private readonly BalanceWriter $balances,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(string $reservationId, Actor $actor): void
    {
        DB::transaction(function () use ($reservationId, $actor): void {
            $reservation = Reservation::query()->whereKey($reservationId)->lockForUpdate()->first();

            if ($reservation === null) {
                throw new ReservationNotFound($reservationId);
            }

            if ($reservation->status === ReservationStatus::Consumed) {
                return;
            }

            if ($reservation->status !== ReservationStatus::Held) {
                throw new InsufficientStock(
                    $reservation->sku_code,
                    $reservation->location_code,
                    0,
                    $reservation->quantity,
                );
            }

            $balance = $this->balances->lock($reservation->sku_id, $reservation->location_id);

            if ($balance === null || $balance->on_hand < $reservation->quantity || $balance->reserved < $reservation->quantity) {
                throw new InsufficientStock(
                    $reservation->sku_code,
                    $reservation->location_code,
                    $balance === null ? 0 : $balance->on_hand - $balance->reserved,
                    $reservation->quantity,
                );
            }

            $this->balances->apply(
                $balance,
                $balance->on_hand - $reservation->quantity,
                $balance->reserved - $reservation->quantity,
            );

            $reservation->status = ReservationStatus::Consumed;
            $reservation->version++;
            $reservation->save();

            StockMovement::query()->create([
                'sku_id' => $reservation->sku_id,
                'sku_code' => $reservation->sku_code,
                'location_id' => $reservation->location_id,
                'location_code' => $reservation->location_code,
                'type' => MovementType::ReservationConsume,
                'quantity' => $reservation->quantity,
                'reservation_id' => $reservation->id,
                'idempotency_key' => $reservation->idempotency_key.':consume',
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            $this->audit->execute(
                $actor,
                'inventory.reservation_consumed',
                'reservation',
                $reservation->id,
                $reservation->warehouse_code,
                ['quantity' => $reservation->quantity],
            );
        }, 3);
    }
}
