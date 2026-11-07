<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Inventory\Models\StockMovement;
use LogicException;
use PHPUnit\Framework\TestCase;

final class AppendOnlyMovementTest extends TestCase
{
    public function test_stock_movements_cannot_be_updated_or_deleted(): void
    {
        $movement = new StockMovement;

        $this->expectException(LogicException::class);
        $movement->update(['quantity' => 1]);
    }
}
