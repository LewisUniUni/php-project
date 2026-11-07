<?php

declare(strict_types=1);

namespace App\Modules\Delivery\Data;

final readonly class StopView
{
    /**
     * @param  list<string>  $allowedTransitions
     */
    public function __construct(
        public string $id,
        public string $reference,
        public ?string $reservationId,
        public string $warehouseCode,
        public string $skuCode,
        public int $quantity,
        public string $status,
        public int $version,
        public array $allowedTransitions,
        public bool $replayed,
    ) {}

    /**
     * @return array{id: string, reference: string, reservation_id: string|null, warehouse_code: string, sku_code: string, quantity: int, status: string, version: int, allowed_transitions: list<string>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'reservation_id' => $this->reservationId,
            'warehouse_code' => $this->warehouseCode,
            'sku_code' => $this->skuCode,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'version' => $this->version,
            'allowed_transitions' => $this->allowedTransitions,
        ];
    }
}
