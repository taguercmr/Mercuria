# Mercuria production deploy (Windows PowerShell)
# Build assets first: npm ci; npm run build
$ErrorActionPreference = "Stop"
Set-Location (Split-Path $PSScriptRoot -Parent)

Write-Host "==> Composer (no-dev)"
composer install --no-dev --optimize-autoloader --no-interaction

Write-Host "==> Laravel optimize"
php artisan config:cache
php artisan route:cache
php artisan view:cache
try { php artisan event:cache } catch { }

Write-Host "==> Migrations"
php artisan migrate --force

Write-Host "==> Storage link"
try { php artisan storage:link } catch { }

Write-Host "==> Done. Ensure public/build exists (Vite production build)."
