# Implementation status

This is a private Origin draft. It is not a GitHub repository, it has not been published, and it has no pull request. Nothing here has been deployed. It is not production-ready.

## Shipped

- Laravel 13.40.0 application on PHP 8.5.12 with `composer.lock`.
- FrankenPHP / Octane image definition and Compose roles for web, Horizon, and `schedule:work`.
- MySQL 8.4, separate Redis 8.10 processes for cache and queue, MongoDB 8.0.
- Inventory ledger: receive, reserve with row locks and a version column, release, and consume.
- Transactional outbox, lease relay, and an idempotent consumer that creates one delivery stop.
- Delivery status machine. Delivered consumes the linked reservation.
- Passport client-credentials tokens and scopes. Warehouse session login with operator and planner gates.
- Idempotency-Key on API POSTs. RFC 9457 problem responses for `/api/v1`.
- OpenAPI 3.1 contract, Redocly lint, and Gesso response checks.
- Bounded MongoDB scan events.
- JSON logs with redaction, `/up`, `/ready`, and a hand-written `/metrics` endpoint.
- PHPUnit tests against the real stores. Pint and PHPStan level 8 pass locally.
- GitHub Actions workflow for those gates, plus a Snyk job that fails closed without `SNYK_TOKEN`.

## Deferred

- Putaway, picking, packing, waves, routes, and dock planning.
- Native, cross-platform, and PWA driver apps. PDA scanner SDKs and offline command queues beyond the idempotency key.
- Authorization code and PKCE, refresh tokens, and device binding.
- Partner webhooks and signed callbacks.
- Proof-of-delivery files and GPS retention.
- OpenTelemetry traces and a Prometheus / Grafana / Loki profile. Those binaries are not started, because nothing would export spans to them.
- Production orchestrator, managed databases, TLS termination, and restore drills.
- GitHub secret scanning and push protection. Those are repository settings and do not exist for this draft.

## Required before a shared environment

- Create the GitHub repository only when someone chooses to publish. Do not mirror this draft in the meantime.
- Set `SNYK_TOKEN` and confirm the plan includes Code, Container, and IaC. Until then the Snyk job is red on purpose.
- Replace the local passwords, generate a production `APP_KEY`, and mount Passport keys.
- Point the runtime database user at DML-only credentials. Run migrations as the migrator user before rollout.
- Choose the identity provider if Passport should not stay.
