<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Actions;

use App\Modules\Delivery\Data\StopView;
use App\Modules\Delivery\Models\DeliveryStop;
use App\Support\Database\ReadCap;

final class ListStops
{
    /**
     * @return array<int, StopView>
     */
    public function execute(int $limit = 100): array
    {
        ReadCap::assertFetchSize($limit);

        return DeliveryStop::query()->select(DeliveryStop::readColumns())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (DeliveryStop $stop): StopView => new StopView(
                id: $stop->id,
                reference: $stop->reference,
                reservationId: $stop->reservation_id,
                warehouseCode: $stop->warehouse_code,
                skuCode: $stop->sku_code,
                quantity: $stop->quantity,
                status: $stop->status->value,
                version: $stop->version,
                allowedTransitions: $stop->status->allowedNextValues(),
                replayed: false,
            ))
            ->all();
    }
}
