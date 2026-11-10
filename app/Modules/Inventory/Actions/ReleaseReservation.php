<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Inventory\BalanceWriter;
use App\Modules\Inventory\Data\ReservationView;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Enums\ReservationStatus;
use App\Modules\Inventory\Exceptions\ReservationNotFound;
use App\Modules\Inventory\Exceptions\ReservationNotReleasable;
use App\Modules\Inventory\Models\Reservation;
use App\Modules\Inventory\Models\StockMovement;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;

final class ReleaseReservation
{
    public function __construct(
        private readonly BalanceWriter $balances,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(string $reservationId, Actor $actor): ReservationView
    {
        return DB::transaction(function () use ($reservationId, $actor): ReservationView {
            $reservation = Reservation::query()->select(Reservation::readColumns())->whereKey($reservationId)->lockForUpdate()->first();

            if ($reservation === null) {
                throw new ReservationNotFound($reservationId);
            }

            if ($reservation->status === ReservationStatus::Released) {
                return $this->view($reservation, true);
            }

            if ($reservation->status !== ReservationStatus::Held) {
                throw new ReservationNotReleasable($reservation->status->value);
            }

            $balance = $this->balances->lock($reservation->sku_id, $reservation->location_id);

            if ($balance === null) {
                throw new ReservationNotFound($reservationId);
            }

            $this->balances->apply(
                $balance,
                $balance->on_hand,
                max(0, $balance->reserved - $reservation->quantity),
            );

            $reservation->status = ReservationStatus::Released;
            $reservation->version++;
            $reservation->save();

            StockMovement::query()->create([
                'sku_id' => $reservation->sku_id,
                'sku_code' => $reservation->sku_code,
                'location_id' => $reservation->location_id,
                'location_code' => $reservation->location_code,
                'type' => MovementType::ReservationRelease,
                'quantity' => $reservation->quantity,
                'reservation_id' => $reservation->id,
                'idempotency_key' => $reservation->idempotency_key.':release',
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            $this->audit->execute(
                $actor,
                'inventory.reservation_released',
                'reservation',
                $reservation->id,
                $reservation->warehouse_code,
                ['quantity' => $reservation->quantity],
            );

            return $this->view($reservation, false);
        }, 3);
    }

    private function view(Reservation $reservation, bool $replayed): ReservationView
    {
        return new ReservationView(
            id: $reservation->id,
            skuCode: $reservation->sku_code,
            locationCode: $reservation->location_code,
            quantity: $reservation->quantity,
            status: $reservation->status->value,
            reference: $reservation->reference,
            warehouseCode: $reservation->warehouse_code,
            version: $reservation->version,
            replayed: $replayed,
        );
    }
}
