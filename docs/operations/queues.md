# Queues

Horizon is the queue consumer. It reads the queue Redis, not the cache Redis. Compose starts it as the `worker` service.

`php artisan horizon:status` is the worker health check. On deploy, `php artisan horizon:terminate` asks workers to finish the current job and exit. The Compose stop grace period is 70 seconds. Job timeout is 30 seconds. The supervisor timeout is 60 seconds. Workers also recycle after 1000 jobs or 3600 seconds.

Jobs retry 5 times with backoffs of 1, 5, 15, and 60 seconds, and they stop 15 minutes after they were queued. Failed jobs land in `failed_jobs`. That table is the dead-letter queue. Inspect it before retrying:

```bash
php artisan queue:failed
php artisan queue:retry <id>
```

Retrying runs the job again. The outbox consumer no-ops when `processed_messages` already contains the message id, so a retry after a successful side effect does not create a second delivery stop.

The queue Redis uses `noeviction` and AOF every second. A full queue returns errors instead of deleting jobs. A crash can still lose about a second of acknowledged data. The outbox lease expires and the scheduler can dispatch the message again.

Cache eviction cannot delete queue jobs because the cache is a different process. Flushing the cache Redis drops sessions, rate limits, and scheduler locks. It does not drop the queue.
