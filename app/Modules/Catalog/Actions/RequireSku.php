<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\SkuRef;
use App\Modules\Catalog\Exceptions\UnknownCatalogItem;
use App\Modules\Catalog\Models\Sku;

final class RequireSku
{
    public function execute(string $code): SkuRef
    {
        $sku = Sku::query()->select(Sku::readColumns())->where('code', $code)->first();

        if ($sku === null) {
            throw UnknownCatalogItem::sku($code);
        }

        return new SkuRef($sku->id, $sku->code, $sku->name, $sku->unit);
    }
}
