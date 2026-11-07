# Backup and restore

This Compose file is a development topology. It is not a backup system.

MySQL holds stock, reservations, delivery stops, the outbox, processed-message ids, idempotency responses, OAuth clients, and audit events. A restore must include those tables together. Restoring the ledger without the outbox can drop unpublished messages. Restoring the outbox without `processed_messages` can apply a message twice; the consumer's unique key stops a second side effect only when that row is restored too.

Production should use managed MySQL with point-in-time recovery. Practice a restore onto a throwaway instance. Do not treat a logical dump taken while the app is writing as a consistent snapshot unless the dump tool provides one.

MongoDB holds scan events only. Losing it does not change on-hand quantity. Back it up if the scan history matters. The collection has a JSON schema and a unique index on `event_id`; a restore that skips indexes will accept duplicates and oversized payloads until the migration is applied again.

Redis cache is disposable. Redis queue AOF is a best effort. After a queue loss, run `php artisan outbox:relay` so leased-but-unpublished messages are dispatched again. Messages already marked processed are not reapplied.

Passport private keys must be backed up with the same care as the database, or every token becomes unverifiable. They are not in the image.
