<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use MongoDB\Laravel\Connection;
use Throwable;

final class ReadyController
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'mysql' => $this->mysql(),
            'redis_cache' => $this->redis('cache'),
            'redis_queue' => $this->redis('default'),
            'mongodb' => $this->mongodb(),
        ];

        $ready = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $ready ? 'ready' : 'unavailable',
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }

    private function mysql(): bool
    {
        try {
            DB::connection('mysql')->select('select 1 as ready');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function redis(string $connection): bool
    {
        try {
            $pong = Redis::connection($connection)->command('ping');

            return $pong === true || $pong === 'PONG' || $pong === '+PONG';
        } catch (Throwable) {
            return false;
        }
    }

    private function mongodb(): bool
    {
        try {
            $connection = DB::connection('mongodb');
            if (! $connection instanceof Connection) {
                return false;
            }

            $connection->getDatabase()->command(['ping' => 1]);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
