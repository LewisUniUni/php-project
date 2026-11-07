<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Delivery\Enums\StopStatus;
use PHPUnit\Framework\TestCase;

final class StopStatusTest extends TestCase
{
    public function test_planned_can_dispatch_or_cancel(): void
    {
        $this->assertTrue(StopStatus::Planned->canTransitionTo(StopStatus::Dispatched));
        $this->assertTrue(StopStatus::Planned->canTransitionTo(StopStatus::Cancelled));
        $this->assertFalse(StopStatus::Planned->canTransitionTo(StopStatus::Delivered));
    }

    public function test_delivered_is_terminal(): void
    {
        $this->assertSame([], StopStatus::Delivered->allowedNext());
    }

    public function test_in_transit_can_succeed_or_fail(): void
    {
        $this->assertSame(
            [StopStatus::Delivered, StopStatus::Failed],
            StopStatus::InTransit->allowedNext(),
        );
    }
}
