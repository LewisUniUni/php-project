<?php

declare(strict_types=1);

namespace App\Http\Problem;

use App\Modules\Catalog\Exceptions\UnknownCatalogItem;
use App\Modules\Delivery\Exceptions\InvalidTransition;
use App\Modules\Delivery\Exceptions\StopNotFound;
use App\Modules\DeviceSync\Exceptions\IdempotencyConflict;
use App\Modules\DeviceSync\Exceptions\MissingIdempotencyKey;
use App\Modules\Inventory\Exceptions\InsufficientStock;
use App\Modules\Inventory\Exceptions\ReservationNotFound;
use App\Modules\Inventory\Exceptions\ReservationNotReleasable;
use App\Support\Runtime\CorrelationContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class ProblemResponse
{
    public static function for(Throwable $exception, Request $request): Response
    {
        $requestId = app(CorrelationContext::class)->requestId();
        [$status, $type, $title, $detail, $extra] = self::describe($exception);

        $body = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getPathInfo(),
            'request_id' => $requestId,
        ];

        if ($extra !== []) {
            $body['errors'] = $extra;
        }

        return response()->json($body, $status, [
            'Content-Type' => 'application/problem+json',
        ]);
    }

    /**
     * @return array{0: int, 1: string, 2: string, 3: string, 4: array<string, list<string>>}
     */
    private static function describe(Throwable $exception): array
    {
        if ($exception instanceof ValidationException) {
            /** @var array<string, list<string>> $errors */
            $errors = $exception->errors();

            return [422, 'urn:quay:problem:validation', 'The request is invalid', 'One or more fields are invalid.', $errors];
        }

        if ($exception instanceof MissingIdempotencyKey) {
            return [400, 'urn:quay:problem:idempotency-key', 'Idempotency-Key is required', $exception->getMessage(), []];
        }

        if ($exception instanceof AuthenticationException) {
            return [401, 'urn:quay:problem:unauthenticated', 'Authentication is required', 'Provide a valid bearer token.', []];
        }

        if ($exception instanceof AuthorizationException) {
            return [403, 'urn:quay:problem:forbidden', 'The token does not allow this action', 'The access token is missing a required scope.', []];
        }

        if ($exception instanceof ThrottleRequestsException) {
            return [429, 'urn:quay:problem:rate-limit', 'Too many requests', 'The client has exceeded its rate limit.', []];
        }

        if ($exception instanceof UnknownCatalogItem) {
            return [404, 'urn:quay:problem:unknown-catalog-item', 'Catalog item was not found', $exception->getMessage(), []];
        }

        if ($exception instanceof ReservationNotFound || $exception instanceof StopNotFound || $exception instanceof ModelNotFoundException) {
            return [404, 'urn:quay:problem:not-found', 'The resource was not found', 'No matching resource exists.', []];
        }

        if ($exception instanceof InsufficientStock) {
            return [409, 'urn:quay:problem:insufficient-stock', 'Insufficient stock', sprintf(
                'Location %s has %d available for %s; requested %d.',
                $exception->locationCode,
                $exception->available,
                $exception->skuCode,
                $exception->requested,
            ), []];
        }

        if ($exception instanceof ReservationNotReleasable) {
            return [409, 'urn:quay:problem:reservation-not-releasable', 'Reservation cannot be released', $exception->getMessage(), []];
        }

        if ($exception instanceof InvalidTransition) {
            return [409, 'urn:quay:problem:invalid-transition', 'Delivery status cannot change that way', $exception->getMessage(), []];
        }

        if ($exception instanceof IdempotencyConflict) {
            return [409, 'urn:quay:problem:idempotency-conflict', 'Idempotency-Key conflict', $exception->getMessage(), []];
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();

            return [$status, 'urn:quay:problem:http', 'The request was rejected', 'The server rejected the request.', []];
        }

        return [500, 'urn:quay:problem:internal', 'The request failed', 'The server could not complete the request.', []];
    }
}
