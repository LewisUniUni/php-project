<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScanEventRequest;
use App\Modules\Telemetry\Actions\FindScanEvent;
use App\Modules\Telemetry\Actions\ListScanEvents;
use App\Modules\Telemetry\Actions\RecordScanEvent;
use App\Modules\Telemetry\Data\ScanEventView;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class ScanEventController extends Controller
{
    public function index(ListScanEvents $events): JsonResponse
    {
        $rows = $events->execute();

        return response()->json([
            'data' => array_map(static fn (ScanEventView $row): array => $row->toArray(), $rows),
            'meta' => ['limit' => 50],
        ]);
    }

    public function store(StoreScanEventRequest $request, RecordScanEvent $record): JsonResponse
    {
        $device = $request->input('device');
        $result = $record->execute(
            eventId: $request->string('event_id')->toString(),
            deviceId: $request->string('device_id')->toString(),
            symbology: $request->string('symbology')->toString(),
            payload: $request->string('payload')->toString(),
            warehouseCode: $request->filled('warehouse_code') ? $request->string('warehouse_code')->toString() : null,
            recordedAt: CarbonImmutable::parse($request->string('recorded_at')->toString()),
            device: is_array($device) ? $this->device($device) : null,
        );

        return response()->json([
            'data' => $result->toArray(),
            'meta' => ['replayed' => $result->replayed],
        ], $result->replayed ? 200 : 201);
    }

    public function show(string $eventId, FindScanEvent $find): JsonResponse
    {
        $view = $find->execute($eventId);

        if ($view === null) {
            abort(404);
        }

        return response()->json(['data' => $view->toArray()]);
    }

    /**
     * @param  array<mixed>  $device
     * @return array{model?: string, firmware?: string}
     */
    private function device(array $device): array
    {
        $clean = [];

        if (isset($device['model']) && is_string($device['model'])) {
            $clean['model'] = $device['model'];
        }

        if (isset($device['firmware']) && is_string($device['firmware'])) {
            $clean['firmware'] = $device['firmware'];
        }

        return $clean;
    }
}
