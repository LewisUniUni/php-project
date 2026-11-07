<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Data;

final readonly class ReceiptView
{
    public function __construct(
        public BalanceView $balance,
        public bool $replayed,
    ) {}
}
