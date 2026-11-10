<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\LocationRef;
use App\Modules\Catalog\Exceptions\UnknownCatalogItem;
use App\Modules\Catalog\Models\Location;

final class RequireLocation
{
    public function execute(string $code): LocationRef
    {
        $location = Location::query()->select(Location::readColumns())->where('code', $code)->first();

        if ($location === null) {
            throw UnknownCatalogItem::location($code);
        }

        return new LocationRef($location->id, $location->code, $location->name, $location->zone);
    }
}
