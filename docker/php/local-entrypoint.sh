#!/usr/bin/env bash
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
PORTAL="${PORTAL_CONTEXT:-all}"

export APP_CONFIG_CACHE="/tmp/royal-config-${ROLE}-${PORTAL}.php"
export APP_ROUTES_CACHE="/tmp/royal-routes-${ROLE}-${PORTAL}.php"
export VIEW_COMPILED_PATH="/tmp/royal-views-${ROLE}-${PORTAL}"
mkdir -p "$VIEW_COMPILED_PATH" \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs

exec "$@"
