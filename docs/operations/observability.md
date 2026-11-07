# Observability

What is implemented:

- JSON logs on stderr, with `request_id`, `trace_id`, and redaction.
- `job_id` on queue jobs when the worker provides one.
- `/up` for process liveness.
- `/ready` for MySQL, cache Redis, queue Redis, and MongoDB.
- `/metrics` in Prometheus text format: HTTP counters, pending outbox rows, scheduler heartbeat age.
- Horizon's own dashboard at `/horizon` for an authenticated operator.
- A slow-query warning when a single statement exceeds `SLOW_QUERY_MS` (default 200). Bindings are not logged.

What is not implemented:

- OpenTelemetry spans, metrics export, or an OTLP endpoint.
- Auto-instrumentation. The PHP extension was not installed. PECL's stable extension is 1.4.2 and the Ubuntu package checked on this machine was 1.2.1. Neither was verified under Octane.
- Prometheus, Grafana, Loki, or Tempo containers. A profile that only displayed empty dashboards would overstate the telemetry.

The `trace_id` field is for log correlation. A `traceparent` header is parsed when it is present. Nothing is sent to a collector.

`/metrics` counters live on the cache Redis. Eviction can reset them. The outbox gauge is read from MySQL on each scrape.
