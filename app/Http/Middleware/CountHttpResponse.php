<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class CountHttpResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        try {
            Redis::connection('cache')->command('hincrby', [
                'quay:http_requests',
                $request->getMethod().'|'.$response->getStatusCode(),
                1,
            ]);
        } catch (Throwable) {
            // Metrics must not fail the request when the cache Redis is down.
        }

        return $response;
    }
}
