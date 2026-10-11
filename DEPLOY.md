# Mercuria — déploiement (hébergement sans Node en prod)

## Prérequis
- PHP 8.3+, extensions Laravel (pdo, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, gd)
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
CAMERPAY_*  (ou identifiants chiffrés dans Admin > Paiements)
QUEUE_CONNECTION=database   # ou sync si pas de worker
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
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
- Webhook CamerPay : `POST /webhooks/camerpay` sur le domaine HTTPS public.
- Renseigner le token API et le secret de callback dans `/admin/payments`. Activer Live dans CamerPay après les tests sandbox ; en production, Mercuria refuse les confirmations sandbox.
- Import mercuriale (par année) :
  `php artisan mercuriale:import --year=2026 --fresh --activate`
  `php artisan mercuriale:import --year=2027 --source=docs/_mercuriale_2027.txt --fresh --activate`
- Ou via console admin : `/admin/mercuriale` (upload .txt + activation)
- Initialisation des plans : `php artisan db:seed --force` (ne crée aucun compte et ne remplace pas les mots de passe).
- Admin plateforme : `php artisan mercuria:admin votre-adresse@example.com` ; le mot de passe est saisi de façon masquée, jamais passé en argument.
- Administrateur existant : `php artisan mercuria:admin votre-adresse@example.com --reset-password` pour remplacer explicitement son mot de passe.
- Contrôle bloquant avant ouverture : `php artisan mercuria:production-check`.
- Smoke test : `php artisan mercuria:smoke`
- Configurer un worker supervisé `php artisan queue:work --tries=3 --timeout=90`, une supervision HTTPS et des sauvegardes hors serveur (base, fichiers et APP_KEY), puis tester une restauration sur une base séparée.
- Le contrôle automatique ne certifie pas les systèmes externes : valider la réception email, le paiement Live et la restauration avant ouverture.

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
