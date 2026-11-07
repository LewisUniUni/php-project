<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class HeartbeatStatusCommand extends Command
{
    protected $signature = 'ops:heartbeat-status';

    protected $description = 'Exit non-zero when the scheduler heartbeat is missing or stale';

    public function handle(): int
    {
        $heartbeat = Cache::get('scheduler:heartbeat');

        if (! is_string($heartbeat)) {
            $this->error('Scheduler heartbeat is missing.');

            return self::FAILURE;
        }

        $stamp = strtotime($heartbeat);

        if ($stamp === false || (time() - $stamp) > 180) {
            $this->error('Scheduler heartbeat is stale.');

            return self::FAILURE;
        }

        $this->info('Scheduler heartbeat is fresh.');

        return self::SUCCESS;
    }
}
