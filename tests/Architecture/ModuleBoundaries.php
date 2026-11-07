<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class ModuleBoundaries
{
    public function test_inventory_does_not_depend_on_delivery_or_telemetry(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Modules\Inventory'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\Modules\Delivery'),
                Selector::inNamespace('App\Modules\Telemetry'),
            )
            ->because('inventory commits stock and an outbox row; delivery and telemetry consume that fact elsewhere');
    }

    public function test_inventory_does_not_use_catalog_eloquent_models(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Modules\Inventory'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('App\Modules\Catalog\Models'))
            ->because('catalog is read through its public actions and DTOs');
    }

    public function test_delivery_does_not_use_inventory_eloquent_models(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Modules\Delivery'))
            ->shouldNot()->dependOn()
            ->classes(Selector::inNamespace('App\Modules\Inventory\Models'))
            ->because('consuming a reservation goes through Inventory\Actions\ConsumeReservation');
    }

    public function test_http_does_not_use_module_eloquent_models(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http'))
            ->shouldNot()->dependOn()
            ->classes(
                Selector::inNamespace('App\Modules\Catalog\Models'),
                Selector::inNamespace('App\Modules\Inventory\Models'),
                Selector::inNamespace('App\Modules\Delivery\Models'),
                Selector::inNamespace('App\Modules\Outbox\Models'),
                Selector::inNamespace('App\Modules\Telemetry\Documents'),
                Selector::inNamespace('App\Modules\DeviceSync\Models'),
                Selector::inNamespace('App\Modules\Audit\Models'),
            )
            ->because('HTTP calls module actions');
    }
}
