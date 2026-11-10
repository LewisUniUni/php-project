# Local development

Requirements: Docker, and PHP 8.5 with the `redis`, `mongodb`, and `pdo_mysql` extensions if you run tests on the host. This draft was exercised on PHP 8.5.12.

```bash
cp .env.example .env
php artisan key:generate
docker compose up -d mysql redis-cache redis-queue mongodb
docker compose --profile migrate run --rm migrate
docker compose build web
docker compose up -d web worker scheduler
docker compose exec web php artisan db:seed
```

Open [http://127.0.0.1:8411](http://127.0.0.1:8411).

Local operator: `avery@quay.local` / `local-operator-password`.

Local planner: `blair@quay.local` / `local-planner-password`.

Those passwords are development defaults from `.env.example`.

MongoDB 8.0.34 refuses to start when the kernel reports a version between 6.19.0 and 7.0.13. Ubuntu 7.0 kernels, including GitHub's `ubuntu-latest` runners, always report `7.0.0` even when built from the fixed upstream 7.0.14. `compose.yaml` and CI therefore set `GLIBC_TUNABLES=glibc.pthread.rseq=1` on the MongoDB container, which hands rseq to glibc so TCMalloc uses per-thread caches and the startup check passes. Remove it once `mongo:8.0.37` ([SERVER-131779](https://jira.mongodb.org/browse/SERVER-131779)) is published and pinned. See [SERVER-121912](https://jira.mongodb.org/browse/SERVER-121912).

The web container uses the DML-only database user. The migrate service uses `logistics_migrator`. Starting the web container does not migrate. `/up` can succeed before the schema exists. `/ready` stays unavailable until MySQL, both Redis processes, and MongoDB answer, and the warehouse pages need the schema.

Host tests, with the database ports published:

```bash
composer install
php artisan test
composer lint
composer analyse
```

`phpunit.xml` points at `127.0.0.1` and the migrator user. It does not use SQLite.

Client secrets for local API calls are the `*_CLIENT_SECRET` values in `.env`. Seeding prints each new client's id. `POST /oauth/token` with `grant_type=client_credentials` returns a bearer token.
