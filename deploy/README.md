# Mise en production — paris-colere.org

Deux variantes :

| Variante | Fichiers | Quand |
|----------|----------|--------|
| **FrankenPHP** (recommandé si vos autres projets utilisent déjà FrankenPHP) | `compose.frankenphp.yaml`, `deploy-frankenphp.sh` | Caddy dans le conteneur (80/443), client Vue monté dans l’image |
| **nginx devant Docker** | `compose.server.yaml`, `deploy.sh` | TLS et statiques sur nginx, API en `127.0.0.1:9080` |

Le client appelle l’API en `/api` (URLs relatives) : tout doit être servi sous **https://paris-colere.org**.

## FrankenPHP — enchaînement après le clone

1. **`deploy/.env`** complet (`APP_SECRET`, `POSTGRES_PASSWORD`, `CADDY_MERCURE_JWT_SECRET`, `ADMIN_*`, `SERVER_NAME=paris-colere.org`).
2. **DNS** : enregistrement **A** `paris-colere.org` → IP du VPS (Caddy obtient Let’s Encrypt sur le port 80).
3. **Postgres** : `./deploy/scripts/db-up.sh` (si pas déjà fait).
4. **Stack complète** : `./deploy/scripts/deploy-frankenphp.sh` (build Vue + `docker compose` prod).
5. **Admin** :

```bash
cd api
docker compose -f compose.yaml -f compose.prod.yaml -f ../deploy/compose.frankenphp.yaml \
  --env-file ../deploy/.env exec php bin/console app:create-admin
```

6. **Ports 80/443** : doivent être libres pour ce compose (ou alignés sur la convention de vos autres stacks FrankenPHP). Si un reverse proxy central occupe déjà 443, utilisez la variante nginx + `compose.server.yaml` ou publiez ce conteneur sur un port interne comme vos autres apps.

Volumes persistants : Postgres (`database_data`), uploads admin (`paris_colere_uploads`), certificats Caddy (`caddy_data`).

## 1. DNS

Chez le registrar, pointer le domaine vers l’IP du VPS :

| Type | Nom | Valeur |
|------|-----|--------|
| A | `@` | IP du VPS |
| A | `www` | IP du VPS (optionnel, redirigé vers apex) |

Attendre la propagation (`dig paris-colere.org +short`).

## 2. Prérequis sur le VPS

Comme vos autres stacks :

- Docker Engine + plugin Compose v2
- nginx
- certbot (`python3-certbot-nginx` sur Debian)
- Node.js 20+ (build du client ; `npm run lint` demande Node 22 en local, le build prod suffit en 20)
- Git, `rsync`

Cloner le dépôt, par ex. `/srv/paris-colere`.

### Phase 1 — clone + Postgres seulement

Sur le VPS :

```bash
sudo mkdir -p /srv
cd /srv
git clone https://github.com/ssepheriades/paris-colere.git
cd paris-colere
git pull   # après chaque push depuis votre machine de dev

cp deploy/env.example deploy/.env
chmod 600 deploy/.env
# Éditer deploy/.env : POSTGRES_PASSWORD (openssl rand -hex 32), POSTGRES_USER, POSTGRES_DB
```

Démarrer la base :

```bash
chmod +x deploy/scripts/db-up.sh
./deploy/scripts/db-up.sh
```

Vérifier :

```bash
cd api
docker compose --env-file ../deploy/.env ps
docker compose --env-file ../deploy/.env exec database \
  psql -U app -d paris_colere -c 'SELECT version();'
```

(Les identifiants `app` / `paris_colere` suivent `deploy/.env`.)

Ensuite : secrets complets, nginx, puis `./deploy/scripts/deploy.sh` pour l’API et le client.

## 3. Secrets

```bash
cp deploy/env.example deploy/.env
chmod 600 deploy/.env
```

Remplir au minimum :

```bash
openssl rand -hex 32   # APP_SECRET, POSTGRES_PASSWORD, CADDY_MERCURE_JWT_SECRET
```

`ADMIN_EMAIL` / `ADMIN_PASSWORD` : compte EasyAdmin en prod (pas les valeurs de dev).

## 4. nginx + certificat

```bash
sudo cp deploy/nginx/paris-colere.org.conf /etc/nginx/sites-available/paris-colere.org
sudo ln -sf /etc/nginx/sites-available/paris-colere.org /etc/nginx/sites-enabled/
# Commenter temporairement les blocs listen 443 ssl si certificats absents
sudo nginx -t && sudo systemctl reload nginx

sudo certbot --nginx -d paris-colere.org -d www.paris-colere.org
sudo nginx -t && sudo systemctl reload nginx
```

Adapter les chemins SSL si votre certbot utilise un layout différent (comme sur vos autres vhosts).

## 5. Déployer l’application

Depuis la racine du dépôt :

```bash
chmod +x deploy/scripts/deploy.sh
./deploy/scripts/deploy.sh
```

Ou à la main :

```bash
cd client && npm ci && npm run build
sudo rsync -a --delete client/dist/ /var/www/paris-colere.org/

cd api
docker compose -f compose.yaml -f compose.prod.yaml -f ../deploy/compose.server.yaml \
  --env-file ../deploy/.env up -d --build --wait
```

**Premier déploiement — compte admin :**

```bash
cd api
docker compose -f compose.yaml -f compose.prod.yaml -f ../deploy/compose.server.yaml \
  --env-file ../deploy/.env exec php bin/console app:create-admin
```

Les migrations Doctrine s’exécutent au démarrage du conteneur `php`.

## 6. Données initiales (optionnel)

Les fixtures (personnes, affaires, etc.) ne sont chargées qu’en **dev/test**. En prod :

- saisie via `/admin`, ou
- import SQL / script maison, ou
- une fois : copie contrôlée depuis un dump de dev (hors scope de ce guide).

## 7. Vérifications

- https://paris-colere.org — client Vue
- https://paris-colere.org/api — JSON-LD API Platform
- https://paris-colere.org/admin — EasyAdmin (login)

```bash
curl -sI https://paris-colere.org/api | head
docker compose -f compose.yaml -f compose.prod.yaml -f ../deploy/compose.server.yaml \
  --env-file ../deploy/.env ps
```

## 8. Mises à jour

```bash
git pull
./deploy/scripts/deploy.sh
```

Les images Docker sont reconstruites ; Postgres et `paris_colere_uploads` (photos admin) persistent via volumes.

## Variante : FrankenPHP seul sur 443

Si ce VPS n’a **pas** nginx devant (projet isolé), vous pouvez publier 80/443 directement depuis `compose.yaml`, définir `SERVER_NAME=paris-colere.org` et laisser Caddy obtenir Let’s Encrypt (volumes `caddy_data`). Dans ce cas, ne pas utiliser `deploy/compose.server.yaml` ni le vhost nginx ; il faudrait en plus servir le build Vue (Caddy `file_server` ou image combinée). La variante nginx ci-dessus reste la plus proche d’un VPS multi-sites.

## Fichiers

| Fichier | Rôle |
|---------|------|
| `deploy/env.example` | Modèle de `deploy/.env` |
| `deploy/compose.server.yaml` | Ports locaux, volume uploads, proxies Symfony |
| `deploy/nginx/paris-colere.org.conf` | Site nginx |
| `deploy/scripts/deploy.sh` | Build client + compose prod |
| `api/.env.prod` | CORS / DEFAULT_URI committés pour `APP_ENV=prod` |
