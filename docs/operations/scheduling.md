# Scheduler

Run exactly one `scheduler` container:

```text
php artisan schedule:work
```

Tasks:

| Command | Interval | Lock |
| --- | --- | --- |
| `outbox:relay` | every minute | `onOneServer`, `withoutOverlapping` expires after 5 minutes |
| `ops:heartbeat` | every minute | `onOneServer`, `withoutOverlapping` expires after 2 minutes |

Locks are cache Redis keys. If the scheduler dies mid-task, the lock remains until it expires. Do not start a second scheduler to "clear" it.

`ops:heartbeat` writes `scheduler:heartbeat` with a 180 second TTL. `php artisan ops:heartbeat-status` exits non-zero when that key is missing or older than 180 seconds. The Compose health check allows a 90 second start period so the first tick can land.

`schedule:work` is not started by the web or worker containers.
