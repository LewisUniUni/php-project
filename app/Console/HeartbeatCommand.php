<?php

declare(strict_types=1);

namespace App\Console;

use App\Support\Runtime\CorrelationContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class HeartbeatCommand extends Command
{
    protected $signature = 'ops:heartbeat';

    protected $description = 'Record a scheduler heartbeat in the cache Redis';

    public function handle(CorrelationContext $context): int
    {
        $context->replace(
            requestId: null,
            traceId: bin2hex(random_bytes(16)),
            spanId: bin2hex(random_bytes(8)),
        );
        Log::withContext($context->logContext());

        Cache::put('scheduler:heartbeat', now()->toIso8601String(), 180);
        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
