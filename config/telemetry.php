<?php

declare(strict_types=1);

return [
    /*
    | Export stays off unless an operator sets TELEMETRY_EXPORT=true and an
    | OTLP endpoint. Grafana is the visualization product. This process does
    | not know a Loki, Tempo, Mimir, Alloy, or Grafana Cloud address.
    */
    'export' => filter_var(env('TELEMETRY_EXPORT', false), FILTER_VALIDATE_BOOL),
    'endpoint' => env('OTEL_EXPORTER_OTLP_ENDPOINT'),
    'protocol' => env('OTEL_EXPORTER_OTLP_PROTOCOL', 'http/protobuf'),
    'service_name' => env('OTEL_SERVICE_NAME', env('APP_NAME', 'quay')),
];
