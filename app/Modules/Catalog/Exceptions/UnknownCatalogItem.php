<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use RuntimeException;

final class UnknownCatalogItem extends RuntimeException
{
    public static function sku(string $code): self
    {
        return new self($code, 'sku');
    }

    public static function location(string $code): self
    {
        return new self($code, 'location');
    }

    private function __construct(
        public readonly string $itemCode,
        public readonly string $kind,
    ) {
        parent::__construct('Unknown '.$kind.' '.$itemCode.'.');
    }
}
