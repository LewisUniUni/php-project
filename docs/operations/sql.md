# SQL

MySQL runs in strict mode: `STRICT_TRANS_TABLES`, `ERROR_FOR_DIVISION_BY_ZERO`, `NO_ENGINE_SUBSTITUTION`, and `ONLY_FULL_GROUP_BY`. Eloquent strictness is on outside production: lazy loading, discarded attributes, and missing attributes throw.

The stock list test asserts the request stays within `QUERY_BUDGET` (default 15). That budget is a regression check for this endpoint. It is not a latency guarantee.

Slow statements log a warning with the SQL text and the connection name. They do not log bindings. Review new inventory queries with `EXPLAIN` on a staging copy of the data. Do not run `EXPLAIN ANALYZE` for a statement that writes, in production or anywhere else it would change rows.

Indexes created for this slice:

| Table | Index | Why it exists |
| --- | --- | --- |
| `skus.code`, `locations.code` | unique | Catalog lookup |
| `stock_balances (sku_id, location_id)` | unique | One balance row, and the row the reservation locks |
| `stock_movements (sku_id, location_id, created_at)` | index | Ledger reads in that order |
| `stock_movements.idempotency_key` | unique, nullable | Receipt replay |
| `reservations.idempotency_key` | unique | Hold replay |
| `reservations (sku_id, location_id, status)` | index | Open holds for a balance |
| `delivery_stops.reference` | unique | One stop per delivery reference |
| `outbox_messages (status, leased_until)` | index | Relay scan |
| `processed_messages (message_id, consumer)` | unique | Consumer deduplication |
| `http_idempotency_keys (actor, idempotency_key)` | unique | HTTP replay |
| `audit_events (subject_type, subject_id)` and `created_at` | index | Audit lookup |

An index is not evidence that a query is fast on a large table. Check the plan when the data grows.
