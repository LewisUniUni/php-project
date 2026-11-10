<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Outbox\Actions\CountPendingMessages;
use App\Support\Values\ConfiguredInt;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;
use UnexpectedValueException;

final class MetricsController
{
    public function __invoke(Request $request, CountPendingMessages $pendingMessages): Response
    {
        $token = config('operations.metrics_token');

        if (is_string($token) && $token !== '') {
            $presented = $request->bearerToken();
            if (! is_string($presented) || ! hash_equals($token, $presented)) {
                return response('forbidden', 403, ['Content-Type' => 'text/plain; charset=UTF-8']);
            }
        }

        $lines = [
            '# HELP quay_http_requests_total HTTP responses recorded by this process family.',
            '# TYPE quay_http_requests_total counter',
        ];

        try {
            $counts = Redis::connection('cache')->command('hgetall', ['quay:http_requests']);
            if (is_array($counts)) {
                foreach ($counts as $field => $value) {
                    if (! is_string($field)) {
                        continue;
                    }
                    try {
                        $count = ConfiguredInt::parse($value, 'quay:http_requests.'.$field);
                    } catch (UnexpectedValueException) {
                        continue;
                    }

                    [$method, $status] = array_pad(explode('|', $field, 2), 2, 'unknown');
                    $lines[] = sprintf(
                        'quay_http_requests_total{method="%s",status="%s"} %d',
                        $this->label($method),
                        $this->label($status),
                        $count,
                    );
                }
            }
        } catch (Throwable) {
            $lines[] = '# redis_cache_unavailable 1';
        }

        $pending = 0;
        try {
            $pending = $pendingMessages->execute();
        } catch (Throwable) {
            $pending = -1;
        }

        $lines[] = '# HELP quay_outbox_pending Outbox messages waiting for a relay lease.';
        $lines[] = '# TYPE quay_outbox_pending gauge';
        $lines[] = 'quay_outbox_pending '.$pending;

        $age = -1;
        $heartbeat = Cache::get('scheduler:heartbeat');
        if (is_string($heartbeat)) {
            $stamp = strtotime($heartbeat);
            if ($stamp !== false) {
                $age = max(0, time() - $stamp);
            }
        }

        $lines[] = '# HELP quay_scheduler_heartbeat_age_seconds Age of the scheduler heartbeat. -1 means no heartbeat has been written.';
        $lines[] = '# TYPE quay_scheduler_heartbeat_age_seconds gauge';
        $lines[] = 'quay_scheduler_heartbeat_age_seconds '.$age;

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=UTF-8',
        ]);
    }

    private function label(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '_', $value) ?? 'unknown';
    }
}
