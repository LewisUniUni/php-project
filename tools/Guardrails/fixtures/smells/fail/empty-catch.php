<?php

declare(strict_types=1);

function fail_empty_catch(): void
{
    try {
        $value = 1;
    } catch (RuntimeException $exception) {
    }
}

function fail_empty_catch_without_variable(): void
{
    try {
        $value = 1;
    } catch (RuntimeException) {
    }
}
