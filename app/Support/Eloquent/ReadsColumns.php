<?php

declare(strict_types=1);

namespace App\Support\Eloquent;

trait ReadsColumns
{
    /**
     * @return list<string>
     */
    abstract public static function readColumns(): array;
}
