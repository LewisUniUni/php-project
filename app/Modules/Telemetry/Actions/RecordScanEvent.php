<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Actions;

use App\Modules\Telemetry\Data\ScanEventView;
use App\Modules\Telemetry\Documents\ScanEvent;
use Carbon\CarbonImmutable;
use Throwable;

final class RecordScanEvent
{
    /**
     * @param  array{model?: string, firmware?: string}|null  $device
     */
    public function execute(
        string $eventId,
        string $deviceId,
        string $symbology,
        string $payload,
        ?string $warehouseCode,
        CarbonImmutable $recordedAt,
        ?array $device,
    ): ScanEventView {
        $existing = ScanEvent::query()->select(ScanEvent::readColumns())->where('event_id', $eventId)->first();

        if ($existing instanceof ScanEvent) {
            return $this->view($existing, true);
        }

        try {
            $created = ScanEvent::query()->create([
                'event_id' => $eventId,
                'device_id' => $deviceId,
                'symbology' => $symbology,
                'payload' => $payload,
                'warehouse_code' => $warehouseCode,
                'recorded_at' => $recordedAt,
                'device' => $device,
            ]);
        } catch (Throwable $exception) {
            if (! str_contains($exception->getMessage(), 'E11000')) {
                throw $exception;
            }

            $created = ScanEvent::query()->select(ScanEvent::readColumns())->where('event_id', $eventId)->first();

            if (! $created instanceof ScanEvent) {
                throw $exception;
            }

            return $this->view($created, true);
        }

        return $this->view($created, false);
    }

    private function view(ScanEvent $event, bool $replayed): ScanEventView
    {
        $recordedAt = $event->recorded_at;

        return new ScanEventView(
            eventId: $event->event_id,
            deviceId: $event->device_id,
            symbology: $event->symbology,
            payload: $event->payload,
            warehouseCode: $event->warehouse_code,
            recordedAt: $recordedAt->toIso8601String(),
            device: is_array($event->device) ? $event->device : null,
            replayed: $replayed,
        );
    }
}
