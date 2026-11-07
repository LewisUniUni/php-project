#!/bin/sh
set -eu

cd /app
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/frankenphp/config \
    storage/frankenphp/data \
    bootstrap/cache

if [ "$(id -u)" = "0" ]; then
    chown -R app:app storage bootstrap/cache
fi

if [ "${APP_ENV:-local}" = "local" ] && [ ! -f storage/oauth-private.key ]; then
    php artisan passport:keys --force --no-interaction
    if [ "$(id -u)" = "0" ]; then
        chown app:app storage/oauth-private.key storage/oauth-public.key
    fi
fi

if [ "${APP_ENV:-local}" != "local" ] && [ ! -f storage/oauth-private.key ] && [ -z "${PASSPORT_PRIVATE_KEY:-}" ]; then
    echo "Passport signing key is missing. Mount storage/oauth-private.key or set PASSPORT_PRIVATE_KEY." >&2
    exit 1
fi

if [ "$(id -u)" = "0" ]; then
    exec setpriv --reuid=app --regid=app --init-groups -- "$@"
fi

exec "$@"
