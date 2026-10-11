#!/usr/bin/env bash
# Mercuria production deploy (shared hosting / VPS without Node on prod)
# Build assets locally first: npm ci && npm run build
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

echo "==> Composer (no-dev)"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Laravel optimize"
php artisan config:clear
php artisan mercuria:production-check --configuration-only --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

echo "==> Migrations"
php artisan migrate --force

echo "==> Storage link"
php artisan storage:link 2>/dev/null || true

echo "==> Restart queue workers"
php artisan queue:restart

echo "==> Validate production readiness"
php artisan mercuria:production-check --no-interaction

echo "==> Clear stale caches if APP_ENV=local"
if [[ "${APP_ENV:-production}" == "local" ]]; then
  php artisan optimize:clear
fi

echo "==> Done. Ensure public/build exists (Vite production build)."
echo "    Upload public/build from CI/local if Node is unavailable on the server."
echo "    Smoke: php artisan mercuria:smoke"
echo "    Search index (optional Meili): php artisan mercuriale:index"
