<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Data\BalanceView;
use App\Modules\Inventory\Models\StockBalance;

final class ListBalances
{
    /**
     * @return array<int, BalanceView>
     */
    public function execute(int $limit = 100): array
    {
        return StockBalance::query()
            ->orderBy('sku_code')
            ->orderBy('location_code')
            ->limit($limit)
            ->get()
            ->map(fn (StockBalance $balance): BalanceView => new BalanceView(
                skuCode: $balance->sku_code,
                locationCode: $balance->location_code,
                onHand: $balance->on_hand,
                reserved: $balance->reserved,
                available: $balance->on_hand - $balance->reserved,
                version: $balance->version,
            ))
            ->all();
    }
}
