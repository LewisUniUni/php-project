<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransitionStopRequest;
use App\Modules\Delivery\Actions\FindStop;
use App\Modules\Delivery\Actions\ListStops;
use App\Modules\Delivery\Actions\TransitionStop;
use App\Modules\Delivery\Enums\StopStatus;
use App\Modules\Identity\CurrentActor;
use Illuminate\Http\JsonResponse;

final class DeliveryStopController extends Controller
{
    public function index(ListStops $stops): JsonResponse
    {
        $rows = $stops->execute();

        return response()->json([
            'data' => array_map(static fn ($row) => $row->toArray(), $rows),
            'meta' => ['limit' => 100],
        ]);
    }

    public function show(string $stop, FindStop $find): JsonResponse
    {
        return response()->json(['data' => $find->execute($stop)->toArray()]);
    }

    public function transition(TransitionStopRequest $request, string $stop, TransitionStop $transition, CurrentActor $actors): JsonResponse
    {
        $status = StopStatus::from($request->string('status')->toString());
        $result = $transition->execute($stop, $status, $actors->fromRequest($request));

        return response()->json([
            'data' => $result->toArray(),
            'meta' => ['replayed' => $result->replayed],
        ]);
    }
}
