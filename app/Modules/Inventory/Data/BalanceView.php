<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Data;

final readonly class BalanceView
{
    public function __construct(
        public string $skuCode,
        public string $locationCode,
        public int $onHand,
        public int $reserved,
        public int $available,
        public int $version,
    ) {}

    /**
     * @return array{sku_code: string, location_code: string, on_hand: int, reserved: int, available: int, version: int}
     */
    public function toArray(): array
    {
        return [
            'sku_code' => $this->skuCode,
            'location_code' => $this->locationCode,
            'on_hand' => $this->onHand,
            'reserved' => $this->reserved,
            'available' => $this->available,
            'version' => $this->version,
        ];
    }
}
