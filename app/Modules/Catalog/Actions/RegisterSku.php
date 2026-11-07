<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\SkuRef;
use App\Modules\Catalog\Models\Sku;

final class RegisterSku
{
    public function execute(string $code, string $name, string $unit = 'each'): SkuRef
    {
        $sku = Sku::query()->firstOrCreate(
            ['code' => $code],
            ['name' => $name, 'unit' => $unit],
        );

        return new SkuRef($sku->id, $sku->code, $sku->name, $sku->unit);
    }
}
