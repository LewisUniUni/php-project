<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Actions;

use App\Modules\Inventory\Data\ReservationView;
use App\Modules\Inventory\Exceptions\ReservationNotFound;
use App\Modules\Inventory\Models\Reservation;

final class FindReservation
{
    public function execute(string $id): ReservationView
    {
        $reservation = Reservation::query()->select(Reservation::readColumns())->find($id);

        if ($reservation === null) {
            throw new ReservationNotFound($id);
        }

        return new ReservationView(
            id: $reservation->id,
            skuCode: $reservation->sku_code,
            locationCode: $reservation->location_code,
            quantity: $reservation->quantity,
            status: $reservation->status->value,
            reference: $reservation->reference,
            warehouseCode: $reservation->warehouse_code,
            version: $reservation->version,
            replayed: false,
        );
    }
}
