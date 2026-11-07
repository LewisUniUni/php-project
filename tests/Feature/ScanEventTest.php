<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\Telemetry\Documents\ScanEvent;
use Illuminate\Support\Str;
use Studio\Gesso\Laravel\ValidatesOpenApiSchema;
use Tests\TestCase;

final class ScanEventTest extends TestCase
{
    use ValidatesOpenApiSchema;

    public function test_scan_events_are_idempotent_and_reject_oversized_payloads(): void
    {
        $this->actAsClient(['telemetry.write'], 'pda-scanner');
        $eventId = (string) Str::uuid();
        $body = [
            'event_id' => $eventId,
            'device_id' => 'pda-14',
            'symbology' => 'code128',
            'payload' => 'SKU-PALLET-WRAP',
            'warehouse_code' => 'WH-1',
            'recorded_at' => '2026-11-06T23:40:00+00:00',
            'device' => ['model' => 'TC52', 'firmware' => '1.4.2'],
        ];

        $created = $this->postJson('/api/v1/scan-events', $body, ['Idempotency-Key' => (string) Str::uuid()]);
        $created->assertCreated();
        $this->assertResponseMatchesOpenApiSchema($created);

        $again = $this->postJson('/api/v1/scan-events', $body, ['Idempotency-Key' => (string) Str::uuid()]);
        $again->assertOk();
        $again->assertJsonPath('meta.replayed', true);
        $this->assertSame(1, ScanEvent::query()->count());

        $huge = $this->postJson('/api/v1/scan-events', [
            ...$body,
            'event_id' => (string) Str::uuid(),
            'payload' => str_repeat('x', 513),
        ], ['Idempotency-Key' => (string) Str::uuid()]);
        $huge->assertUnprocessable();
        $this->assertResponseMatchesOpenApiSchema($huge);
        $this->assertSame(1, ScanEvent::query()->count());
    }
}
