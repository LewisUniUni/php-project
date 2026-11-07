<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

final readonly class SkuRef
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public string $unit,
    ) {}
}
