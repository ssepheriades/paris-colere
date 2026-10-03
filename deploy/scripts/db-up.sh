#!/usr/bin/env bash
# Démarre uniquement PostgreSQL (volume persistant). À lancer depuis le dépôt cloné.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
ENV_FILE="${ENV_FILE:-$ROOT/deploy/.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Créer $ENV_FILE (voir deploy/env.example), au minimum POSTGRES_PASSWORD." >&2
  exit 1
fi

cd "$ROOT/api"

docker compose \
  --env-file "$ENV_FILE" \
  up -d database --wait

echo "→ Postgres prêt (réseau Docker interne, pas de port publié en prod)."
echo "  Test depuis un conteneur : docker compose --env-file $ENV_FILE exec database pg_isready -U \${POSTGRES_USER:-app} -d \${POSTGRES_DB:-paris_colere}"
