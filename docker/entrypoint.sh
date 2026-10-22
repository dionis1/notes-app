#!/bin/sh
set -eu
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s storage/app/docker-app-key ]; then
        php -r 'echo "base64:".base64_encode(random_bytes(32));' > storage/app/docker-app-key
        chmod 600 storage/app/docker-app-key
    fi
    APP_KEY="$(cat storage/app/docker-app-key)"
    export APP_KEY
fi
if [ "${DB_CONNECTION:-}" = sqlite ] && [ "${DB_DATABASE:-}" != ':memory:' ]; then
    mkdir -p "$(dirname "$DB_DATABASE")"
    touch "$DB_DATABASE"
fi
if [ "${1:-}" = php ] && [ "${2:-}" = artisan ] && [ "${3:-}" = serve ]; then
    php artisan migrate --force --no-interaction
fi
exec "$@"
