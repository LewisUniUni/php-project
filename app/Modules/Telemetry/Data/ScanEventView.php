<?php

declare(strict_types=1);

namespace App\Modules\Telemetry\Data;

final readonly class ScanEventView
{
    /**
     * @param  array{model?: string, firmware?: string}|null  $device
     */
    public function __construct(
        public string $eventId,
        public string $deviceId,
        public string $symbology,
        public string $payload,
        public ?string $warehouseCode,
        public string $recordedAt,
        public ?array $device,
        public bool $replayed,
    ) {}

    /**
     * @return array{event_id: string, device_id: string, symbology: string, payload: string, warehouse_code: string|null, recorded_at: string, device: array{model?: string, firmware?: string}|null}
     */
    public function toArray(): array
    {
        return [
            'event_id' => $this->eventId,
            'device_id' => $this->deviceId,
            'symbology' => $this->symbology,
            'payload' => $this->payload,
            'warehouse_code' => $this->warehouseCode,
            'recorded_at' => $this->recordedAt,
            'device' => $this->device,
        ];
    }
}
