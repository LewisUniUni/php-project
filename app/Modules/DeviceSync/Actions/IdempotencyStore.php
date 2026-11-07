<?php

declare(strict_types=1);

namespace App\Modules\DeviceSync\Actions;

use App\Modules\DeviceSync\Exceptions\IdempotencyConflict;
use App\Modules\DeviceSync\Models\HttpIdempotencyKey;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\HttpFoundation\Response;

final class IdempotencyStore
{
    public function findReplay(string $actor, string $key, string $requestHash): ?Response
    {
        $existing = HttpIdempotencyKey::query()
            ->where('actor', $actor)
            ->where('idempotency_key', $key)
            ->first();

        if ($existing === null) {
            return null;
        }

        if (! hash_equals($existing->request_hash, $requestHash)) {
            throw new IdempotencyConflict;
        }

        return response($existing->response_body, $existing->response_status)
            ->header('Content-Type', 'application/json')
            ->header('Idempotent-Replayed', 'true');
    }

    public function remember(string $actor, string $key, string $requestHash, Response $response): void
    {
        if ($response->getStatusCode() >= 500) {
            return;
        }

        try {
            HttpIdempotencyKey::query()->create([
                'actor' => $actor,
                'idempotency_key' => $key,
                'request_hash' => $requestHash,
                'response_status' => $response->getStatusCode(),
                'response_body' => $response->getContent(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request stored the response first.
        }
    }
}
