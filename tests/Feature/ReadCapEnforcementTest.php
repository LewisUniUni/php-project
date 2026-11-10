<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Delivery\Actions\ListStops;
use App\Modules\Telemetry\Actions\ListScanEvents;
use App\Support\Database\ProhibitedRead;
use Tests\TestCase;

final class ReadCapEnforcementTest extends TestCase
{
    public function test_a_delivery_list_over_100_fails_instead_of_truncating(): void
    {
        $this->expectException(ProhibitedRead::class);

        app(ListStops::class)->execute(101);
    }

    public function test_a_scan_list_over_100_fails_instead_of_truncating(): void
    {
        $this->expectException(ProhibitedRead::class);

        app(ListScanEvents::class)->execute(101);
    }

    public function test_a_bounded_delivery_list_still_runs(): void
    {
        $this->assertSame([], app(ListStops::class)->execute(100));
    }

    public function test_a_bounded_scan_list_still_runs(): void
    {
        $this->assertSame([], app(ListScanEvents::class)->execute(50));
    }
}
