<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Audit\Actions\RecordAudit;
use App\Modules\Catalog\Actions\RequireLocation;
use App\Modules\Catalog\Actions\RequireSku;
use App\Modules\Inventory\BalanceWriter;
use App\Modules\Inventory\Data\BalanceView;
use App\Modules\Inventory\Data\ReceiptView;
use App\Modules\Inventory\Enums\MovementType;
use App\Modules\Inventory\Models\StockBalance;
use App\Modules\Inventory\Models\StockMovement;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;

final class ReceiveStock
{
    public function __construct(
        private readonly RequireSku $skus,
        private readonly RequireLocation $locations,
        private readonly BalanceWriter $balances,
        private readonly RecordAudit $audit,
    ) {}

    public function execute(
        string $skuCode,
        string $locationCode,
        int $quantity,
        string $idempotencyKey,
        Actor $actor,
    ): ReceiptView {
        return DB::transaction(function () use ($skuCode, $locationCode, $quantity, $idempotencyKey, $actor): ReceiptView {
            $existing = StockMovement::query()->select(StockMovement::readColumns())->where('idempotency_key', $idempotencyKey)->first();

            if ($existing !== null) {
                $balance = $this->balances->lock($existing->sku_id, $existing->location_id);

                if ($balance === null) {
                    return new ReceiptView(new BalanceView(
                        skuCode: $existing->sku_code,
                        locationCode: $existing->location_code,
                        onHand: $quantity,
                        reserved: 0,
                        available: $quantity,
                        version: 0,
                    ), true);
                }

                return new ReceiptView($this->view($balance), true);
            }

            $sku = $this->skus->execute($skuCode);
            $location = $this->locations->execute($locationCode);
            $balance = $this->balances->lockOrCreate($sku, $location);
            $balance = $this->balances->apply($balance, $balance->on_hand + $quantity, $balance->reserved);

            StockMovement::query()->create([
                'sku_id' => $sku->id,
                'sku_code' => $sku->code,
                'location_id' => $location->id,
                'location_code' => $location->code,
                'type' => MovementType::Receipt,
                'quantity' => $quantity,
                'idempotency_key' => $idempotencyKey,
                'actor_type' => $actor->type,
                'actor_id' => $actor->id,
                'occurred_at' => now(),
            ]);

            $this->audit->execute(
                $actor,
                'inventory.received',
                'stock_balance',
                $balance->id,
                null,
                ['sku_code' => $sku->code, 'location_code' => $location->code, 'quantity' => $quantity],
            );

            return new ReceiptView($this->view($balance), false);
        }, 3);
    }

    private function view(StockBalance $balance): BalanceView
    {
        return new BalanceView(
            skuCode: $balance->sku_code,
            locationCode: $balance->location_code,
            onHand: $balance->on_hand,
            reserved: $balance->reserved,
            available: $balance->on_hand - $balance->reserved,
            version: $balance->version,
        );
    }
}
