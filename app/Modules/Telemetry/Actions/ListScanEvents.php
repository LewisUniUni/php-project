<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Actions;

use App\Modules\Telemetry\Data\ScanEventView;
use App\Modules\Telemetry\Documents\ScanEvent;
use App\Support\Database\ReadCap;

final class ListScanEvents
{
    /**
     * @return array<int, ScanEventView>
     */
    public function execute(int $limit = 50): array
    {
        ReadCap::assertFetchSize($limit);

        return ScanEvent::query()->select(ScanEvent::readColumns())
            ->orderByDesc('recorded_at')
            ->limit($limit)
            ->get()
            ->map(function (ScanEvent $event): ScanEventView {
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
            })
            ->all();
    }
}
