<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Catalog\Actions\RequireLocation;
use App\Modules\Catalog\Actions\RequireSku;
use App\Modules\Inventory\BalanceWriter;
use App\Modules\Inventory\Data\ReservationView;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Enums\ReservationStatus;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Models\Reservation;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Outbox\Actions\RecordOutboxMessage;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;

final class ReserveStock
{
    public function __construct(
        private readonly RequireSku $skus,
        private readonly RequireLocation $locations,
        private readonly BalanceWriter $balances,
        private readonly RecordOutboxMessage $outbox,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(
        string $skuCode,
        string $locationCode,
        int $quantity,
        string $reference,
        string $warehouseCode,
        string $idempotencyKey,
        Actor $actor,
    ): ReservationView {
        return DB::transaction(function () use ($skuCode, $locationCode, $quantity, $reference, $warehouseCode, $idempotencyKey, $actor): ReservationView {
            $existing = Reservation::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                return $this->view($existing, true);
            }

            $sku = $this->skus->execute($skuCode);
            $location = $this->locations->execute($locationCode);
            $balance = $this->balances->lock($sku->id, $location->id);
            $available = $balance === null ? 0 : $balance->on_hand - $balance->reserved;

            if ($balance === null || $available < $quantity) {
                throw new InsufficientStock($sku->code, $location->code, $available, $quantity);
            }

            $balance = $this->balances->apply($balance, $balance->on_hand, $balance->reserved + $quantity);

            $reservation = Reservation::query()->create([
                'sku_id' => $sku->id,
                'sku_code' => $sku->code,
                'location_id' => $location->id,
                'location_code' => $location->code,
                'quantity' => $quantity,
                'status' => ReservationStatus::Held,
                'reference' => $reference,
                'warehouse_code' => $warehouseCode,
                'idempotency_key' => $idempotencyKey,
                'version' => 0,
            ]);

            StockMovement::query()->create([
                'sku_id' => $sku->id,
                'sku_code' => $sku->code,
                'location_id' => $location->id,
                'location_code' => $location->code,
                'type' => MovementType::ReservationHold,
                'quantity' => $quantity,
                'reservation_id' => $reservation->id,
                'idempotency_key' => $idempotencyKey.':hold',
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            $this->outbox->execute('inventory.reservation_held', [
                'reservation_id' => $reservation->id,
                'reference' => $reference,
                'warehouse_code' => $warehouseCode,
                'sku_code' => $sku->code,
                'quantity' => $quantity,
            ]);

            $this->audit->execute(
                $actor,
                'inventory.reservation_held',
                'reservation',
                $reservation->id,
                $warehouseCode,
                ['quantity' => $quantity, 'reference' => $reference],
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
