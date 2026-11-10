# syntax=docker/dockerfile:1.7

# The PHP runtime stays the official FrankenPHP 1.13.5 / PHP 8.5.12 Trixie
# image. The FrankenPHP binary is rebuilt from that same tag, commit
# a3663e9e2cc4c258018a28ee068e50cc12f3ed2d, with smallstep/certificates pinned
# to cddde30af36e238f156bb557bfd1f11a05ffe6f9 so Caddy's PKI module links
# go-jose v4 instead of the unfixed v3 module. This is not a v3-to-v4 replace.
# Debian packages stay as the base image ships them. When an official image's
# binary no longer contains go-jose v3, delete the frankenphp-bin stage, the
# COPY below, and docker/frankenphp, and keep only the runtime digest.
FROM dunglas/frankenphp:1.13.5-builder-php8.5.12-trixie@sha256:5b553d5b993d3f0cbe187ee0a6a69b41f30c9ce355442a1b3e5aea24682eaa04 AS frankenphp-bin

ENV PATH=/usr/local/go/bin:${PATH} \
    FRANKENPHP_VERSION=v1.13.5 \
    PHP_VERSION=8.5.12

COPY docker/frankenphp/go.mod docker/frankenphp/go.sum /go/src/app/caddy/

WORKDIR /go/src/app/caddy
RUN go mod download

WORKDIR /go/src/app/caddy/frankenphp
RUN GOBIN=/usr/local/bin \
    ../../go.sh install -ldflags "-w -s -X 'github.com/caddyserver/caddy/v2.CustomVersion=FrankenPHP v1.13.5 PHP 8.5.12 Caddy' -X 'github.com/caddyserver/caddy/v2.CustomBinaryName=frankenphp' -X 'github.com/caddyserver/caddy/v2/modules/caddyhttp.ServerHeader=FrankenPHP Caddy'" -buildvcs=false

FROM dunglas/frankenphp:1.13.5-php8.5.12-trixie@sha256:7e73a27eb7712a5b4073b92f8f0dec91e9400138604b76cc791589faaa6a639c AS php-base

COPY --from=frankenphp-bin /usr/local/bin/frankenphp /usr/local/bin/frankenphp
RUN setcap cap_net_bind_service=+ep /usr/local/bin/frankenphp

ARG COMPOSER_VERSION=2.11.1
ARG COMPOSER_SHA256=6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# ca-certificates and curl are not version-pinned. The base image is already
# digest-pinned, and Snyk Container remains the CVE gate for the OS packages.
# hadolint ignore=DL3008
RUN install-php-extensions \
        bcmath \
        intl \
        mongodb-2.5.7 \
        pcntl \
        pdo_mysql \
        redis-6.3.0 \
        zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl \
    && rm -rf /var/lib/apt/lists/* \
    && curl -fsSL -o /usr/local/bin/composer "https://getcomposer.org/download/${COMPOSER_VERSION}/composer.phar" \
    && echo "${COMPOSER_SHA256}  /usr/local/bin/composer" | sha256sum -c - \
    && chmod +x /usr/local/bin/composer \
    && groupadd --gid 1000 app \
    && useradd --uid 1000 --gid app --create-home --shell /usr/sbin/nologin app

ENV XDG_CONFIG_HOME=/app/storage/frankenphp/config \
    XDG_DATA_HOME=/app/storage/frankenphp/data

WORKDIR /app

FROM php-base AS vendor

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi

FROM php-base AS runtime

COPY --from=vendor /app /app
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R app:app storage bootstrap/cache \
    && chmod +x docker/entrypoint.sh

EXPOSE 8000
ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--workers=2", "--max-requests=500", "--admin-port=2019"]
