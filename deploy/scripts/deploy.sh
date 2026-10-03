#!/usr/bin/env bash
# Déploiement depuis la racine du dépôt sur le VPS (Debian 12 + Docker + nginx).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WEB_ROOT="${WEB_ROOT:-/var/www/paris-colere.org}"
ENV_FILE="${ENV_FILE:-$ROOT/deploy/.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Créer $ENV_FILE à partir de deploy/env.example" >&2
  exit 1
fi

echo "→ Build client Vue"
cd "$ROOT/client"
npm ci
npm run build

echo "→ Sync client vers $WEB_ROOT"
sudo mkdir -p "$WEB_ROOT"
sudo rsync -a --delete "$ROOT/client/dist/" "$WEB_ROOT/"

echo "→ Build & démarrage API (FrankenPHP + Postgres)"
cd "$ROOT/api"
docker compose \
  -f compose.yaml \
  -f compose.prod.yaml \
  -f "$ROOT/deploy/compose.server.yaml" \
  --env-file "$ENV_FILE" \
  up -d --build --wait

echo "→ OK. Pensez à app:create-admin si premier déploiement."
