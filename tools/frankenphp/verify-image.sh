#!/bin/sh
# Verify the built runtime image. This script does not change Snyk thresholds.
set -eu

IMAGE="${1:-quay-platform:ci}"
ROOT="$(CDPATH= cd -- "$(dirname "$0")/../.." && pwd)"
PORT="${SMOKE_PORT:-18411}"
COLLECTOR="${SMOKE_OTLP_ENDPOINT:-http://203.0.113.1:4318}"

if docker info >/dev/null 2>&1; then
    DOCKER=docker
else
    DOCKER="sudo docker"
fi

fail() {
    echo "verify-image: $1" >&2
    exit 1
}

cleanup() {
    $DOCKER rm -f smoke-web smoke-horizon smoke-scheduler >/dev/null 2>&1 || true
    rm -f /tmp/frankenphp-smoke-bin /tmp/frankenphp-dispatch.php 2>/dev/null || sudo rm -f /tmp/frankenphp-smoke-bin /tmp/frankenphp-dispatch.php 2>/dev/null || true
}
trap cleanup EXIT

echo "verify-image: copying binary out of ${IMAGE}"
container="$($DOCKER create "$IMAGE")"
$DOCKER cp "$container":/usr/local/bin/frankenphp /tmp/frankenphp-smoke-bin
$DOCKER rm "$container" >/dev/null

go version -m /tmp/frankenphp-smoke-bin > /tmp/frankenphp-modules.txt
if grep -F 'github.com/go-jose/go-jose/v3' /tmp/frankenphp-modules.txt >/dev/null; then
    fail 'go-jose v3 is still in the binary'
fi
v4="$(awk '$2 == "github.com/go-jose/go-jose/v4" { print $3; exit }' /tmp/frankenphp-modules.txt)"
awk -v v="${v4#v}" 'BEGIN {
    split(v, got, ".")
    split("4.1.6", need, ".")
    for (i = 1; i <= 3; i++) {
        g = got[i] + 0
        n = need[i] + 0
        if (g > n) exit 0
        if (g < n) exit 1
    }
    exit 0
}' || fail "go-jose v4 ${v4:-missing} is below 4.1.6"
echo "verify-image: go-jose ${v4}; v3 absent"

version="$($DOCKER run --rm --entrypoint frankenphp "$IMAGE" version)"
echo "$version"
echo "$version" | grep -F 'FrankenPHP v1.13.5' >/dev/null || fail 'FrankenPHP version is not v1.13.5'
echo "$version" | grep -F 'PHP 8.5.12' >/dev/null || fail 'PHP version is not 8.5.12'

modules="$($DOCKER run --rm --entrypoint php "$IMAGE" -m)"
for module in curl xml xmlreader xmlwriter dom intl zip bcmath pcntl pdo_mysql redis mongodb; do
    echo "$modules" | grep -xi "$module" >/dev/null || fail "PHP module ${module} is not loaded"
done
echo "verify-image: required PHP modules are loaded"

entrypoint_out="$($DOCKER run --rm \
    -e APP_ENV=local \
    -e APP_KEY="${APP_KEY:-base64:XGUZxtnQ4I6OrQLQLqHkD6mapLrJdpYhWl9UE3Hqjtc=}" \
    -e TELEMETRY_EXPORT=false \
    "$IMAGE" php -r 'echo "command-ran\n";')"
echo "$entrypoint_out" | grep -F 'command-ran' >/dev/null || fail 'entrypoint did not run the command'
echo "$entrypoint_out" | grep -Ei 'migrat' && fail 'entrypoint ran migrations' || true

APP_KEY_VALUE="${APP_KEY:-base64:XGUZxtnQ4I6OrQLQLqHkD6mapLrJdpYhWl9UE3Hqjtc=}"
common_env="
    -e APP_ENV=local
    -e APP_KEY=${APP_KEY_VALUE}
    -e APP_DEBUG=false
    -e DB_CONNECTION=mysql
    -e DB_HOST=${DB_HOST:-127.0.0.1}
    -e DB_PORT=${DB_PORT:-3306}
    -e DB_DATABASE=${DB_DATABASE:-logistics_test}
    -e DB_USERNAME=${DB_USERNAME:-root}
    -e DB_PASSWORD=${DB_PASSWORD:-local-root-password}
    -e CACHE_STORE=redis
    -e QUEUE_CONNECTION=redis
    -e SESSION_DRIVER=redis
    -e SESSION_CONNECTION=cache
    -e REDIS_CLIENT=phpredis
    -e REDIS_CACHE_HOST=${REDIS_CACHE_HOST:-127.0.0.1}
    -e REDIS_CACHE_PORT=${REDIS_CACHE_PORT:-6379}
    -e REDIS_QUEUE_HOST=${REDIS_QUEUE_HOST:-127.0.0.1}
    -e REDIS_QUEUE_PORT=${REDIS_QUEUE_PORT:-6380}
    -e MONGODB_URI=${MONGODB_URI:-mongodb://127.0.0.1:27017}
    -e MONGODB_DATABASE=${MONGODB_DATABASE:-logistics_test}
    -e TELEMETRY_EXPORT=false
    -e OTEL_EXPORTER_OTLP_ENDPOINT=${COLLECTOR}
    -e LOG_CHANNEL=stderr
"

# shellcheck disable=SC2086
$DOCKER run -d --name smoke-web --network host $common_env "$IMAGE" \
    php artisan octane:frankenphp --host=127.0.0.1 --port="$PORT" --workers=1 --max-requests=50 >/dev/null

ready=0
i=0
while [ "$i" -lt 40 ]; do
    if curl -fsS "http://127.0.0.1:${PORT}/up" >/dev/null 2>&1 \
        && curl -fsS "http://127.0.0.1:${PORT}/ready" | grep -F '"status":"ready"' >/dev/null 2>&1; then
        ready=1
        break
    fi
    i=$((i + 1))
    sleep 1
done
[ "$ready" -eq 1 ] || fail '/up or /ready did not succeed while telemetry export was off'
echo "verify-image: /up and /ready passed with TELEMETRY_EXPORT=false and an unroutable collector"

# shellcheck disable=SC2086
$DOCKER run -d --name smoke-horizon --network host $common_env "$IMAGE" php artisan horizon >/dev/null
sleep 5
cat > /tmp/frankenphp-dispatch.php <<'PHP'
<?php

declare(strict_types=1);

require '/app/vendor/autoload.php';
$app = require '/app/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Cache::store('redis')->put('frankenphp:smoke', 'pending', 180);
dispatch(function (): void {
    Illuminate\Support\Facades\Cache::store('redis')->put('frankenphp:smoke', 'done', 180);
});
echo "dispatched\n";
PHP
# shellcheck disable=SC2086
$DOCKER run --rm --network host -v /tmp/frankenphp-dispatch.php:/tmp/frankenphp-dispatch.php:ro $common_env \
    "$IMAGE" php /tmp/frankenphp-dispatch.php | grep -F dispatched >/dev/null

job=0
i=0
while [ "$i" -lt 30 ]; do
    # shellcheck disable=SC2086
    if $DOCKER run --rm --network host $common_env "$IMAGE" php -r 'require "/app/vendor/autoload.php"; $app=require "/app/bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo Illuminate\Support\Facades\Cache::store("redis")->get("frankenphp:smoke");' | grep -F done >/dev/null; then
        job=1
        break
    fi
    i=$((i + 1))
    sleep 1
done
[ "$job" -eq 1 ] || fail 'Horizon did not complete the queued job'
echo "verify-image: Horizon completed the queued job"

# shellcheck disable=SC2086
$DOCKER run -d --name smoke-scheduler --network host $common_env "$IMAGE" php artisan schedule:work >/dev/null
beat=0
i=0
while [ "$i" -lt 100 ]; do
    # shellcheck disable=SC2086
    if $DOCKER exec smoke-scheduler php artisan ops:heartbeat-status >/dev/null 2>&1; then
        beat=1
        break
    fi
    i=$((i + 1))
    sleep 1
done
[ "$beat" -eq 1 ] || fail 'scheduler heartbeat did not become fresh'
echo "verify-image: scheduler heartbeat is fresh"
