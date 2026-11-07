<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class HeartbeatCommand extends Command
{
    protected $signature = 'ops:heartbeat';

    protected $description = 'Record a scheduler heartbeat in the cache Redis';

    public function handle(): int
    {
        Cache::put('scheduler:heartbeat', now()->toIso8601String(), 180);
        $this->info('Scheduler heartbeat recorded.');

        return self::SUCCESS;
    }
}
