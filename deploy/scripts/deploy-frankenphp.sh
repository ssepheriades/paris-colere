#!/usr/bin/env bash
# Déploiement FrankenPHP (Caddy 443 + Postgres). Pas de nginx requis pour ce projet.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="${ENV_FILE:-$ROOT/deploy/.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Créer $ENV_FILE à partir de deploy/env.example" >&2
  exit 1
fi

echo "→ Build client Vue"
cd "$ROOT/client"
npm ci
npm run build

echo "→ Build & démarrage FrankenPHP + Postgres"
cd "$ROOT/api"
docker compose \
  -f compose.yaml \
  -f compose.prod.yaml \
  -f "$ROOT/deploy/compose.frankenphp.yaml" \
  --env-file "$ENV_FILE" \
  up -d --build --wait

echo "→ OK. Premier déploiement : app:create-admin (voir deploy/README.md)."
