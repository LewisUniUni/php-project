<?php

declare(strict_types=1);

function pass_log_and_rethrow(): void
{
    try {
        $value = 1;
    } catch (RuntimeException $exception) {
        error_log($exception->getMessage());
        throw $exception;
    }
}
