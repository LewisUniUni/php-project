<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Actions;

use App\Modules\Delivery\Data\StopView;
use App\Modules\Delivery\Enums\StopStatus;
use App\Modules\Delivery\Models\DeliveryStop;
use App\Modules\Outbox\Exceptions\InvalidOutboxPayload;
use Illuminate\Database\UniqueConstraintViolationException;

final class EnsurePlannedStop
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function execute(array $payload): StopView
    {
        $reference = $this->stringField($payload, 'reference');
        $reservationId = $this->stringField($payload, 'reservation_id');
        $warehouseCode = $this->stringField($payload, 'warehouse_code');
        $skuCode = $this->stringField($payload, 'sku_code');
        $quantity = $payload['quantity'] ?? null;

        if (! is_int($quantity) || $quantity < 1) {
            throw new InvalidOutboxPayload('Reservation payload quantity must be a positive integer.');
        }

        $existing = DeliveryStop::query()->where('reference', $reference)->first();

        if ($existing !== null) {
            return $this->view($existing, true);
        }

        try {
            $stop = DeliveryStop::query()->create([
                'reference' => $reference,
                'reservation_id' => $reservationId,
                'warehouse_code' => $warehouseCode,
                'sku_code' => $skuCode,
                'quantity' => $quantity,
                'status' => StopStatus::Planned,
                'version' => 0,
            ]);
        } catch (UniqueConstraintViolationException) {
            $stop = DeliveryStop::query()->where('reference', $reference)->firstOrFail();

            return $this->view($stop, true);
        }

        return $this->view($stop, false);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function stringField(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new InvalidOutboxPayload('Reservation payload is missing '.$key.'.');
        }

        return $value;
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
