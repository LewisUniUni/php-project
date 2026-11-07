<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Actions;

use App\Modules\Delivery\Data\StopView;
use App\Modules\Delivery\Exceptions\StopNotFound;
use App\Modules\Delivery\Models\DeliveryStop;

final class FindStop
{
    public function execute(string $id): StopView
    {
        $stop = DeliveryStop::query()->find($id);

        if ($stop === null) {
            throw new StopNotFound($id);
        }

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
            replayed: false,
        );
    }
}
