<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\Identity\CurrentActor;
use App\Support\Runtime\CorrelationContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignCorrelationId
{
    public function __construct(
        private readonly CorrelationContext $correlation,
        private readonly CurrentActor $actors,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id');
        if (! is_string($requestId) || ! Str::isUuid($requestId)) {
            $requestId = (string) Str::uuid();
        }

        $traceId = $this->traceId($request->headers->get('traceparent'));
        $actor = $this->actors->fromRequest($request);
        $warehouse = $request->headers->get('X-Warehouse-Code');

        $this->correlation->replace(
            requestId: $requestId,
            traceId: $traceId,
            clientId: $actor->type === 'client' ? $actor->id : null,
            warehouseId: is_string($warehouse) && $warehouse !== '' ? $warehouse : null,
        );

        Context::add('request_id', $requestId);
        Context::add('trace_id', $traceId);
        Log::shareContext($this->correlation->logContext());

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private function traceId(?string $traceparent): string
    {
        if (is_string($traceparent) && preg_match('/^00-([a-f0-9]{32})-[a-f0-9]{16}-[a-f0-9]{2}$/', $traceparent, $matches) === 1) {
            return $matches[1];
        }

        return bin2hex(random_bytes(16));
    }
}
