<?php

declare(strict_types=1);

namespace App\Support\Runtime;

use Illuminate\Foundation\Application;

final class ResetWorkerRequestState
{
    public function handle(mixed $event): void
    {
        $sandbox = is_object($event) && isset($event->sandbox) ? $event->sandbox : null;

        if (! $sandbox instanceof Application) {
            app(CorrelationContext::class)->clear();

            return;
        }

        if ($sandbox->resolved(CorrelationContext::class)) {
            $sandbox->make(CorrelationContext::class)->clear();
        }
    }
}
