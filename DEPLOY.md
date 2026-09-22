# Mercuria — déploiement (hébergement sans Node en prod)

## Prérequis
- PHP 8.2+, extensions Laravel (pdo, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, gd)
- Composer
- MySQL / MariaDB (prod) ou SQLite (dev)
- Build Vite **en local / CI** : `npm ci && npm run build` → déployer `public/build`

## Checklist prod (.env)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.cm
DB_*
MAIL_*
FAPSHI_* / CINETPAY_*  (voir config/payments.php)
QUEUE_CONNECTION=database   # ou sync si pas de worker
SESSION_DRIVER=database
CACHE_STORE=database
```

## Commandes
```bash
# 1. Build assets (machine de build)
npm ci && npm run build

# 2. Sur le serveur
chmod +x scripts/deploy.sh
./scripts/deploy.sh
```

## Post-déploiement
- Pointer le vhost sur `public/`
- Webhooks : `POST /webhooks/fapshi` et `POST /webhooks/cinetpay`
- Import mercuriale (une fois) : `php artisan mercuriale:import`
- Admin plateforme : `php artisan db:seed` → `admin@mercuria.cm` / `password`
- Smoke test : `php artisan mercuria:smoke`

## Optimisations
- `config:cache` / `route:cache` / `view:cache` (inclus dans deploy.sh)
- Indexes catalogue : migration `2026_09_22_080000_*`
- Recherche : SQL par défaut ; Meilisearch optionnel :
  ```
  SCOUT_DRIVER=meilisearch
  MEILISEARCH_HOST=http://127.0.0.1:7700
  MEILISEARCH_KEY=
  MERCURIA_SEARCH_DRIVER=auto
  php artisan mercuriale:index
  ```
  Sans Meilisearch, le fallback SQL reste actif automatiquement.
