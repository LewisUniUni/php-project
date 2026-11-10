<?php

declare(strict_types=1);

namespace App\Support\Telemetry;

use RuntimeException;

final class OtlpExport
{
    public static function boot(): void
    {
        if (self::decision(
            export: filter_var(config('telemetry.export'), FILTER_VALIDATE_BOOL) === true,
            endpoint: is_string(config('telemetry.endpoint')) ? config('telemetry.endpoint') : null,
            testing: app()->runningUnitTests(),
        ) === 'missing-endpoint') {
            throw new RuntimeException('TELEMETRY_EXPORT is enabled, but OTEL_EXPORTER_OTLP_ENDPOINT is empty. Grafana visualizes telemetry; it is not the OTLP receiver. Set the collector endpoint before turning export on.');
        }
    }

    public static function decision(bool $export, ?string $endpoint, bool $testing): string
    {
        if ($testing || ! $export) {
            return 'disabled';
        }

        if ($endpoint === null || $endpoint === '') {
            return 'missing-endpoint';
        }

        return 'configured';
    }
}
