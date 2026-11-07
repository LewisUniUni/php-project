<?php

declare(strict_types=1);

namespace App\Modules\Inventory;

use App\Modules\Catalog\Data\LocationRef;
use App\Modules\Catalog\Data\SkuRef;
use App\Modules\Inventory\Exceptions\ConcurrentModification;
use App\Modules\Inventory\Models\StockBalance;
use Illuminate\Database\UniqueConstraintViolationException;

final class BalanceWriter
{
    public function lockOrCreate(SkuRef $sku, LocationRef $location): StockBalance
    {
        $existing = $this->lock($sku->id, $location->id);

        if ($existing !== null) {
            return $existing;
        }

        try {
            StockBalance::query()->create([
                'sku_id' => $sku->id,
                'sku_code' => $sku->code,
                'location_id' => $location->id,
                'location_code' => $location->code,
                'on_hand' => 0,
                'reserved' => 0,
                'version' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another transaction created the row. Lock it below.
        }

        $created = $this->lock($sku->id, $location->id);

        if ($created === null) {
            throw new ConcurrentModification;
        }

        return $created;
    }

    public function lock(string $skuId, string $locationId): ?StockBalance
    {
        return StockBalance::query()
            ->where('sku_id', $skuId)
            ->where('location_id', $locationId)
            ->lockForUpdate()
            ->first();
    }

    public function apply(StockBalance $balance, int $onHand, int $reserved): StockBalance
    {
        $updated = StockBalance::query()
            ->whereKey($balance->id)
            ->where('version', $balance->version)
            ->update([
                'on_hand' => $onHand,
                'reserved' => $reserved,
                'version' => $balance->version + 1,
                'updated_at' => now(),
            ]);

        if ($updated !== 1) {
            throw new ConcurrentModification;
        }

        $balance->on_hand = $onHand;
        $balance->reserved = $reserved;
        $balance->version++;

        return $balance;
    }
}
