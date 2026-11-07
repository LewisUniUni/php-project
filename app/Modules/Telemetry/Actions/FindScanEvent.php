<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Actions;

use App\Modules\Telemetry\Data\ScanEventView;
use App\Modules\Telemetry\Documents\ScanEvent;

final class FindScanEvent
{
    public function execute(string $eventId): ?ScanEventView
    {
        $event = ScanEvent::query()->where('event_id', $eventId)->first();

        if (! $event instanceof ScanEvent) {
            return null;
        }

        return new ScanEventView(
            eventId: $event->event_id,
            deviceId: $event->device_id,
            symbology: $event->symbology,
            payload: $event->payload,
            warehouseCode: $event->warehouse_code,
            recordedAt: $event->recorded_at->toIso8601String(),
            device: is_array($event->device) ? $event->device : null,
            replayed: false,
        );
    }
}
