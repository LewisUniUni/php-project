<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Modules\DeviceSync\Actions\IdempotencyStore;
use App\Modules\DeviceSync\Exceptions\MissingIdempotencyKey;
use App\Modules\Identity\CurrentActor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequireIdempotencyKey
{
    public function __construct(
        private readonly IdempotencyStore $store,
        private readonly CurrentActor $actors,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->headers->get('Idempotency-Key');

        if (! is_string($key) || ! Str::isUuid($key)) {
            throw new MissingIdempotencyKey;
        }

        $actor = $this->actors->fromRequest($request)->key();
        $hash = hash('sha256', $request->getContent());
        $replay = $this->store->findReplay($actor, $key, $hash);

        if ($replay !== null) {
            return $replay;
        }

        /** @var Response $response */
        $response = $next($request);
        $this->store->remember($actor, $key, $hash, $response);

        return $response;
    }
}
