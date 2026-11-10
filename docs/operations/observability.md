# Observability

What is implemented:

- JSON logs on stderr, with `request_id`, `trace_id`, `span_id`, and redaction.
- The web middleware copies both ids from a W3C `traceparent` header, or generates them.
- The outbox consumer and the scheduler heartbeat each start a log-correlation id. `job_id` is included when the worker provides one.
- `/up` for process liveness.
- `/ready` for MySQL, cache Redis, queue Redis, and MongoDB.
- `/metrics` in Prometheus text format: HTTP counters, pending outbox rows, scheduler heartbeat age.
- Horizon's own dashboard at `/horizon` for an authenticated operator.
- A slow-query warning when a single statement exceeds `SLOW_QUERY_MS` (default 200). Bindings are not logged.
- `config/telemetry.php`. `TELEMETRY_EXPORT` defaults to false. Tests set it false. Turning it on with an empty `OTEL_EXPORTER_OTLP_ENDPOINT` fails boot. Nothing is dialed.

What is not implemented:

- An OpenTelemetry SDK, span exporter, or auto-instrumentation. Those were not added under FrankenPHP/Octane because that combination was not verified and would change worker lifetime.
- A collector, and any Grafana datasource. Grafana displays telemetry. It is not the OTLP receiver. This repo does not name Loki, Tempo, Mimir, Alloy, or a Grafana Cloud tenant.

The log fields are correlation ids. They are not exported traces.

`/metrics` counters live on the cache Redis. Eviction can reset them. The outbox gauge is read from MySQL on each scrape.
