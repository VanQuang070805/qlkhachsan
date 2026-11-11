#!/usr/bin/env bash
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
PORTAL="${PORTAL_CONTEXT:-all}"

if [ -f "/var/www/html/docker/php/vendor.tar" ]; then
    if [ "${ROLE}" = "customer-portal" ] && [ ! -f "/var/www/html/vendor/.ready" ]; then
        echo "[${ROLE}] Extracting vendor.tar into fast Linux volume..."
        tar -xf /var/www/html/docker/php/vendor.tar -C /var/www/html/
        touch /var/www/html/vendor/.ready
    else
        while [ ! -f "/var/www/html/vendor/.ready" ]; do
            sleep 0.5
        done
    fi
fi

export APP_CONFIG_CACHE="/tmp/royal-config-${ROLE}-${PORTAL}.php"
export APP_ROUTES_CACHE="/tmp/royal-routes-${ROLE}-${PORTAL}.php"
export VIEW_COMPILED_PATH="/tmp/royal-views-${ROLE}-${PORTAL}"
mkdir -p "$VIEW_COMPILED_PATH" \
         /tmp/sessions \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs

exec "$@"
