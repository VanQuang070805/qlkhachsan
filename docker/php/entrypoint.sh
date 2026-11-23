#!/usr/bin/env bash
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
PORTAL="${PORTAL_CONTEXT:-all}"

# Isolate bootstrap/cache per container role/portal so customer-app, staff-app, and admin-app
# never overwrite each other's cached APP_URL, SESSION_COOKIE, or PORTAL_CONTEXT.
export APP_CONFIG_CACHE="/tmp/royal-config-${ROLE}-${PORTAL}.php"
export APP_ROUTES_CACHE="/tmp/royal-routes-${ROLE}-${PORTAL}.php"

if [ "$ROLE" = "app-init" ]; then
    php /var/www/html/docker/scripts/bootstrap-db.php
    php artisan migrate --force
    if [ "${DB_BOOTSTRAP_MODE:-snapshot}" = "migrate-seed" ]; then
        php artisan db:seed --force
    fi
    php artisan storage:link --force || true
    echo "[app-init] Database bootstrap and migrations completed."
    exit 0
fi

php artisan config:cache
php artisan view:cache || true

exec "$@"
