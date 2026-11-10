<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ApiListLimitTest extends TestCase
{
    public function test_existing_api_list_actions_keep_their_default_limit(): void
    {
        $actions = [
            'app/Modules/Delivery/Actions/ListStops.php' => 100,
            'app/Modules/Inventory/Actions/ListBalances.php' => 100,
            'app/Modules/Telemetry/Actions/ListScanEvents.php' => 50,
        ];

        foreach ($actions as $action => $limit) {
            $source = file_get_contents($action);
            $this->assertIsString($source);
            $this->assertStringContainsString('int $limit = '.$limit, $source);
            $this->assertStringContainsString('->limit($limit)', $source);
        }
    }
}
