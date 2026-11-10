# Quay

Quay is a greenfield Laravel logistics platform for warehouse stock, reservations, and delivery state. The architecture, including the decisions that were tested and the ones that were left open, is in [docs/architecture.md](docs/architecture.md). The documentation index is [docs/README.md](docs/README.md).

This workspace is a private draft. It is not a published GitHub repository and it is not a production system.

## Run it

```bash
cp .env.example .env
php artisan key:generate
docker compose up -d mysql redis-cache redis-queue mongodb
docker compose --profile migrate run --rm migrate
docker compose build web
docker compose up -d web worker scheduler
docker compose exec web php artisan db:seed
```

The warehouse UI is served by FrankenPHP at [http://127.0.0.1:8411](http://127.0.0.1:8411).

Sign in as `avery@quay.local` with `local-operator-password` after seeding. Details, queues, backups, and the verification record are in [docs/operations](docs/operations/local-development.md).

## Tests

Host PHP must be 8.5 with `pdo_mysql`, `redis`, and `mongodb`. The test suite uses the Compose databases, not SQLite.

```bash
composer install
composer ci:static
php artisan test
```

Coverage, mutation testing, and the decisions still open are recorded in [docs/engineering-playbook-adoption.md](docs/engineering-playbook-adoption.md).

The HTTP contract is [docs/api/openapi.yaml](docs/api/openapi.yaml).
