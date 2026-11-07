<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Data;

final readonly class ReservationView
{
    public function __construct(
        public string $id,
        public string $skuCode,
        public string $locationCode,
        public int $quantity,
        public string $status,
        public string $reference,
        public string $warehouseCode,
        public int $version,
        public bool $replayed,
    ) {}

    /**
     * @return array{id: string, sku_code: string, location_code: string, quantity: int, status: string, reference: string, warehouse_code: string, version: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sku_code' => $this->skuCode,
            'location_code' => $this->locationCode,
            'quantity' => $this->quantity,
            'status' => $this->status,
            'reference' => $this->reference,
            'warehouse_code' => $this->warehouseCode,
            'version' => $this->version,
        ];
    }
}
