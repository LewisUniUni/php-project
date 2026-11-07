<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Exceptions;

use RuntimeException;

final class ConcurrentModification extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Stock balance changed while it was locked.');
    }
}
