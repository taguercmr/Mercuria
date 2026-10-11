# Mercuria

SaaS multi-tenant Freemium (Traguer AI) pour devis, proformas, bons de commande et factures
basés sur la mercuriale MINCOMMERCE Budget 2026 (~49 137 articles).

## Stack

- Laravel 13 + Inertia.js + React + TypeScript + Tailwind
- Paiements : **Fapshi** (primaire) + **CinetPay** (secondaire)
- Recherche : SQL par défaut, **Meilisearch** optionnel (Scout)
- Déploiement : assets React précompilés — **pas de Node en production**

## Démarrage local (XAMPP)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install --legacy-peer-deps
npm run build
php artisan serve
```

- App : http://127.0.0.1:8000
- Admin seed : `admin@mercuria.cm` / `password`
- Smoke : `php artisan mercuria:smoke --base=http://127.0.0.1:8000 --http`
- Health : `GET /health` et `GET /up`

## Mercuriale 2026

```bash
php artisan mercuriale:import --fresh
# optionnel Meilisearch :
# SCOUT_DRIVER=meilisearch + MEILISEARCH_HOST=...
php artisan mercuriale:index
```

## Fonctionnalités

- Catalogue + favoris + recherche (SQL / Meili)
- Clients, documents, PDF, email, modèles, conversion devis→facture
- Équipe multi-users (owner / editor / **viewer lecture seule**)
- **Notifications** in-app (équipe, tickets, abonnement Pro)
- Freemium + checkout Fapshi/CinetPay + API Pro (clés + rate limit)
- Admin : tenants, users, tickets, maintenance
- Exports CSV, duplication documents, audit `activity_logs`

## Rôles tenant

| Rôle | Droits |
|------|--------|
| owner | Tout + invitations équipe |
| editor | Créer / modifier documents & clients |
| viewer | Lecture seule |

## API Pro

Documentation complète dans l’app : **Espace développeurs** (`/app/developers`).

```http
GET /api/v1/mercuriale/articles?q=adaptateur
X-Api-Key: mrc_...
```

Endpoints : rubriques, articles (recherche/pagination), article par référence.
Throttle : 60 req/min. Plan Pro requis.

## Déploiement

Voir [DEPLOY.md](DEPLOY.md) — scripts `scripts/deploy.sh` / `deploy.ps1`.

```bash
npm ci --legacy-peer-deps && npm run build
# déployer public/build + PHP
php artisan migrate --force
php artisan config:cache && php artisan route:cache
# si QUEUE_CONNECTION=database :
php artisan queue:work --tries=3
```

## Paiements & maintenance

- Webhooks : `/webhooks/fapshi`, `/webhooks/cinetpay`
- Console Admin → mode maintenance (bypass `platform_admin`)

## ERA

Projet ERA #81 — sprints 0→7 (fondation → hardening).
