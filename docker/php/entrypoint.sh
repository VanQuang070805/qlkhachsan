#!/usr/bin/env bash
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
PORTAL="${PORTAL_CONTEXT:-all}"

# Isolate bootstrap/cache per container role/portal so customer-app, staff-app, and admin-app
# never overwrite each other's cached APP_URL, SESSION_COOKIE, or PORTAL_CONTEXT.
export APP_CONFIG_CACHE="/tmp/royal-config-${ROLE}-${PORTAL}.php"
export APP_ROUTES_CACHE="/tmp/royal-routes-${ROLE}-${PORTAL}.php"

if [ "$ROLE" = "app-init" ]; then
    echo "[app-init] Waiting for MySQL at ${DB_HOST:-mysql}:${DB_PORT:-3306}..."
    until mysqladmin ping -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent; do
        sleep 2
    done

    TABLE_COUNT=$(mysql -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" -D"${DB_DATABASE}" -N -B -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '${DB_DATABASE}' AND table_name = 'users';")

    if [ "${TABLE_COUNT:-0}" = "0" ]; then
        echo "[app-init] Fresh database volume detected. Bootstrap mode: ${DB_BOOTSTRAP_MODE:-snapshot}"
        if [ "${DB_BOOTSTRAP_MODE:-snapshot}" = "snapshot" ] && [ -f "/var/www/html/database/qlkhachsan.sql" ]; then
            echo "[app-init] Importing initial snapshot database/qlkhachsan.sql..."
            mysql -h"${DB_HOST:-mysql}" -P"${DB_PORT:-3306}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" "${DB_DATABASE}" < /var/www/html/database/qlkhachsan.sql
        fi
        php artisan migrate --force
        if [ "${DB_BOOTSTRAP_MODE:-snapshot}" = "migrate-seed" ]; then
            php artisan db:seed --force
        fi
    else
        echo "[app-init] Existing database schema detected. Running pending migrations only..."
        php artisan migrate --force
    fi

    php artisan storage:link --force || true
    echo "[app-init] Database bootstrap and migrations completed."
    exit 0
fi

php artisan config:cache
php artisan view:cache || true

exec "$@"
