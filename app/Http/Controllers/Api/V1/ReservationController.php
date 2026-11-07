<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Modules\Identity\CurrentActor;
use App\Modules\Inventory\Actions\FindReservation;
use App\Modules\Inventory\Actions\ReleaseReservation;
use App\Modules\Inventory\Actions\ReserveStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request, ReserveStock $reserve, CurrentActor $actors): JsonResponse
    {
        $result = $reserve->execute(
            skuCode: $request->string('sku_code')->toString(),
            locationCode: $request->string('location_code')->toString(),
            quantity: $request->integer('quantity'),
            reference: $request->string('reference')->toString(),
            warehouseCode: $request->string('warehouse_code')->toString(),
            idempotencyKey: $request->string('idempotency_key')->toString(),
            actor: $actors->fromRequest($request),
        );

        return response()->json([
            'data' => $result->toArray(),
            'meta' => ['replayed' => $result->replayed],
        ], $result->replayed ? 200 : 201);
    }

    public function show(string $reservation, FindReservation $find): JsonResponse
    {
        return response()->json(['data' => $find->execute($reservation)->toArray()]);
    }

    public function release(Request $request, string $reservation, ReleaseReservation $release, CurrentActor $actors): JsonResponse
    {
        $result = $release->execute($reservation, $actors->fromRequest($request));

        return response()->json([
            'data' => $result->toArray(),
            'meta' => ['replayed' => $result->replayed],
        ]);
    }
}
