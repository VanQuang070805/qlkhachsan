#!/usr/bin/env bash
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
PORTAL="${PORTAL_CONTEXT:-all}"

if [ ! -f "/var/www/html/artisan" ]; then
    exec "$@"
fi

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

FAST_ROOT="/srv/app"
rm -rf "$FAST_ROOT"
mkdir -p "$FAST_ROOT/docker" "$FAST_ROOT/public"

cp -a /var/www/html/app \
      /var/www/html/bootstrap \
      /var/www/html/config \
      /var/www/html/database \
      /var/www/html/resources \
      /var/www/html/routes \
      /var/www/html/artisan \
      /var/www/html/composer.json \
      "$FAST_ROOT/"

cp -a /var/www/html/docker/scripts "$FAST_ROOT/docker/scripts"

for item in /var/www/html/public/* /var/www/html/public/.[!.]*; do
    [ -e "$item" ] || continue
    base="$(basename "$item")"
    [ "$base" = "storage" ] && continue
    if [ "$base" = "images" ]; then
        ln -s "/var/www/html/public/images" "$FAST_ROOT/public/images"
        continue
    fi
    cp -a "$item" "$FAST_ROOT/public/$base"
done

if [ -f "/var/www/html/.env" ]; then
    cp -a /var/www/html/.env "$FAST_ROOT/.env"
fi

ln -s /var/www/html/vendor "$FAST_ROOT/vendor"

export APP_CONFIG_CACHE="/tmp/royal-config-${ROLE}-${PORTAL}.php"
export APP_ROUTES_CACHE="/tmp/royal-routes-${ROLE}-${PORTAL}.php"
export VIEW_COMPILED_PATH="/tmp/royal-views-${ROLE}-${PORTAL}"

rm -f "$APP_CONFIG_CACHE" \
      "$APP_ROUTES_CACHE" \
      "$FAST_ROOT/bootstrap/cache/config.php" \
      "$FAST_ROOT/bootstrap/cache/routes-v7.php" \
      "$FAST_ROOT/bootstrap/cache/events.php"

mkdir -p "$VIEW_COMPILED_PATH" \
         /tmp/sessions \
         "$FAST_ROOT/bootstrap/cache" \
         "$FAST_ROOT/storage/app/public" \
         "$FAST_ROOT/storage/framework/cache/data" \
         "$FAST_ROOT/storage/framework/sessions" \
         "$FAST_ROOT/storage/framework/views" \
         "$FAST_ROOT/storage/logs"

ln -s "$FAST_ROOT/storage/app/public" "$FAST_ROOT/public/storage"

cd "$FAST_ROOT"
php artisan config:cache >/dev/null 2>&1 || true

echo "[${ROLE}] Fast ext4 runtime ready at ${FAST_ROOT}"
exec "$@"
