# Déploiement Coolify — paris-colere

Stack : **Symfony 8 + API Platform + EasyAdmin** (FrankenPHP / PHP 8.4, worker mode) et **client Vue 3** servi par le même conteneur. Base : **PostgreSQL 18** en ressource Coolify séparée. TLS terminé par **Traefik** (Coolify) ; le conteneur écoute en **HTTP :80**.

Le développement local (`make serve` / `make client`, Docker Compose sous `api/`) n’est pas modifié. Pas de DDEV dans ce dépôt.

## Choix : Dockerfile (recommandé) vs Compose

| Option | Quand | Décision |
|--------|--------|----------|
| **Dockerfile (racine)** | Une app HTTP + DB externe Coolify | **Oui — utiliser celui-ci** |
| Docker Compose | Plusieurs services applicatifs dans le même stack (workers Messenger, sidecars) | Non pour l’instant |

**Pourquoi Dockerfile** : Postgres est une ressource Coolify séparée ; Messenger n’est pas installé ; un seul process web (FrankenPHP) suffit. Coolify build le `Dockerfile` à la racine, injecte les env, monte le volume uploads, et route le domaine via Traefik.

Un compose de smoke test local reste disponible : `deploy/coolify/compose.smoke.yaml` (ne pas le brancher comme source Coolify).

## 1. PostgreSQL 18 (ressource Coolify)

1. Coolify → **New Resource** → **Database** → **PostgreSQL 18**.
2. Noter l’hôte interne, le port, user, password, database.
3. Construire `DATABASE_URL` :

```text
postgresql://USER:PASSWORD@HOST:5432/DBNAME?serverVersion=18&charset=utf8
```

Le `serverVersion=18` est important pour Doctrine.

## 2. Application (Dockerfile)

1. Coolify → **New Resource** → **Application** → repo `ssepheriades/paris-colere`, branche `main`.
2. Build pack : **Dockerfile**.
3. Base directory : `/` (racine).
4. Dockerfile : `Dockerfile`.
5. Port exposé : **80** (HTTP). Ne pas activer HTTPS dans le conteneur.
6. Domaine : `paris-colere.org` (et `www` si besoin) — laisser Coolify / Let’s Encrypt gérer le certificat.

### Volume persistant (uploads EasyAdmin)

| Mount path | Description |
|------------|-------------|
| `/app/public/uploads` | Photos, logos, illustrations admin |

Créer un **named volume** Coolify pointant sur ce chemin. L’image tourne en `www-data` ; le volume doit rester accessible en écriture pour cet UID (souvent UID 33).

### Variables d’environnement

Modèle complet : [`deploy/coolify/env.example`](deploy/coolify/env.example).

| Variable | Obligatoire | Notes |
|----------|-------------|--------|
| `APP_ENV` | oui | `prod` |
| `APP_DEBUG` | oui | `0` |
| `APP_SECRET` | oui | `openssl rand -hex 32` |
| `DEFAULT_URI` | oui | `https://paris-colere.org` |
| `CORS_ALLOW_ORIGIN` | oui | `^https://paris-colere\.org$` |
| `DATABASE_URL` | oui | URL Postgres 18 ci-dessus |
| `SERVER_NAME` | oui | `:80` |
| `CADDY_GLOBAL_OPTIONS` | oui | `auto_https off` |
| `SYMFONY_TRUSTED_PROXIES` | oui | `REMOTE_ADDR` (Traefik hop immédiat) |
| `SYMFONY_TRUSTED_HEADERS` | oui | `x-forwarded-for,x-forwarded-proto,x-forwarded-port,x-forwarded-host` |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | premier deploy | pour `app:create-admin` |
| `API_TOKEN` | recommandé | écritures `/api` ; vide = désactivé |

Aucun secret ne doit être commité. Après changement d’env, redéployer / recréer le conteneur.

## 3. Build & démarrage

Au démarrage, l’entrypoint :

1. attend Postgres (`dbal:run-sql`) ;
2. exécute `doctrine:migrations:migrate --no-interaction --all-or-nothing` (idempotent) ;
3. lance FrankenPHP en **worker mode** (`public/index.php`).

Le build multi-stage : `npm ci` + `npm run build` (client), `composer install --no-dev`, warmup cache, image Debian slim non-root.

## 4. Commandes post-déploiement

Dans le terminal Coolify du conteneur app (ou `docker exec`) :

```bash
# Compte EasyAdmin (une fois)
php bin/console app:create-admin

# Optionnel : résoudre vignettes / oEmbed des sources déjà en base
php bin/console app:resolve-source-embeds
```

Vérifications :

- `https://paris-colere.org/` — client Vue  
- `https://paris-colere.org/api` — API Platform  
- `https://paris-colere.org/admin` — EasyAdmin  

## 5. Messenger / cron

- **Messenger** : non installé → pas de worker `messenger:consume` à lancer.
- **Cron utile** (Coolify Scheduled Task / job périodique sur le conteneur) :

```bash
php bin/console app:resolve-source-embeds
```

Fréquence suggérée : quotidienne ou après imports massifs de sources. Pas de cron obligatoire pour le fonctionnement de base.

## 6. Smoke test local (optionnel)

Nécessite Docker :

```bash
make coolify-smoke
# ou
docker compose -f deploy/coolify/compose.smoke.yaml up --build --wait
curl -sI http://127.0.0.1:9080/api | head
```

Arrêt : `make coolify-smoke-down`.

## Fichiers

| Fichier | Rôle |
|---------|------|
| `Dockerfile` | Image prod Coolify (racine) |
| `.dockerignore` | Contexte de build |
| `deploy/coolify/Caddyfile` | HTTP :80, Vue + Symfony, worker, trusted proxies |
| `deploy/coolify/docker-entrypoint.sh` | Wait DB + migrations |
| `deploy/coolify/env.example` | Liste des variables Coolify |
| `deploy/coolify/compose.smoke.yaml` | Vérification locale avec Postgres 18 |

Le guide VPS nginx historique reste dans [`deploy/README.md`](deploy/README.md).
