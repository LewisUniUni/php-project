<?php

declare(strict_types=1);

function fail_debug(mixed $value): void
{
    var_dump($value);
    dd($value);
}
