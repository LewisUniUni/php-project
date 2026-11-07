<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use App\Modules\Identity\CurrentActor;
use App\Modules\Inventory\Actions\ReceiveStock;
use Illuminate\Http\JsonResponse;

final class ReceiptController extends Controller
{
    public function store(StoreReceiptRequest $request, ReceiveStock $receive, CurrentActor $actors): JsonResponse
    {
        $result = $receive->execute(
            skuCode: $request->string('sku_code')->toString(),
            locationCode: $request->string('location_code')->toString(),
            quantity: $request->integer('quantity'),
            idempotencyKey: $request->string('idempotency_key')->toString(),
            actor: $actors->fromRequest($request),
        );

        return response()->json([
            'data' => $result->balance->toArray(),
            'meta' => ['replayed' => $result->replayed],
        ], $result->replayed ? 200 : 201);
    }
}
