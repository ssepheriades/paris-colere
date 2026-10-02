# Paris Colère

API Symfony 8.1 (API Platform) et client Vue 3 + Vuetify. L’administration EasyAdmin est sur l’API, protégée par un formulaire de connexion. L’API `/api` est publique.

## Prérequis

- PHP 8.4, Composer, Symfony CLI
- Node.js 20 et npm (`npm run lint` dans `client/` demande Node.js 22, à cause d’ESLint)
- PostgreSQL 17 (`/usr/lib/postgresql/17/bin`)

## Démarrage local

Le dépôt embarque un cluster PostgreSQL dans `.data/postgres` (ignoré par git), écouté sur `127.0.0.1:5432`. Le cluster Debian du système, sur le port 5433, n’est pas utilisé : le compte courant n’y a pas de rôle.

```bash
make db
make serve
make client
```

- Client : http://127.0.0.1:5173
- API : http://127.0.0.1:8000/api
- Documentation : http://127.0.0.1:8000/api/docs (compte admin)
- Administration : http://127.0.0.1:8000/admin

Compte admin de développement : `admin@paris-colere.test` / `admin` (`ADMIN_EMAIL` et `ADMIN_PASSWORD` dans `api/.env`).

`make test` lance les tests fonctionnels de l’API.

## Docker

Les fichiers FrankenPHP et PostgreSQL 17 sont dans `api/`. Docker n’est pas branché sur ce WSL pour l’instant. Quand l’intégration Docker Desktop sera active :

```bash
make docker
```

Le conteneur Postgres publie le port **5434** sur la machine, pour laisser le 5432 au cluster local et le 5433 au cluster Debian. L’API Docker écoute en HTTPS sur le port 443.
