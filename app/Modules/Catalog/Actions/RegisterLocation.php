<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\LocationRef;
use App\Modules\Catalog\Models\Location;

final class RegisterLocation
{
    public function execute(string $code, string $name, string $zone): LocationRef
    {
        $location = Location::query()->select(Location::readColumns())->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'zone' => $zone],
        );

        return new LocationRef($location->id, $location->code, $location->name, $location->zone);
    }
}
