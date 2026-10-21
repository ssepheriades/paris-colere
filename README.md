# Paris Colère

API Symfony 8.1 (API Platform) et client Vue 3 + Vuetify. L’administration EasyAdmin est sur l’API, protégée par un formulaire de connexion. La lecture de `/api` est publique ; les écritures de contenu exigent un jeton (voir plus bas).

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

Secrets locaux : `cp api/.env.local.example api/.env.local` puis éditer (fichier gitignoré).

Compte admin de développement par défaut : `admin@paris-colere.test` / `admin` (dans `api/.env.local`).

`make test` lance les tests fonctionnels de l’API.

## Accès par token API

Les lectures (`GET`) restent anonymes. `POST`, `PUT`, `PATCH` et `DELETE` sur les ressources de contenu exigent un jeton statique, lu dans la variable `API_TOKEN`. Le jeton reçoit le rôle `ROLE_API`. `/admin` et `/api/docs` restent protégés par le formulaire de connexion. Le formulaire de contact (`POST /api/contacts`) reste public ; les messages ne sont pas exposés en lecture.

Si `API_TOKEN` est vide, aucun `Bearer` n’authentifie — y compris un jeton vide. Un jeton incorrect répond `401`, même sur un `GET`. Sans en-tête, les lectures publiques continuent de répondre `200`.

Le jeton ne contient que des lettres, des chiffres et les caractères `- _ + ~ / .` (`openssl rand -hex 32` convient). Il s’envoie en `Authorization: Bearer`.

```bash
curl -s -H 'Accept: application/ld+json' \
  http://127.0.0.1:8000/api/themes

curl -s -X POST \
  -H 'Accept: application/ld+json' \
  -H 'Content-Type: application/ld+json' \
  -H "Authorization: Bearer $API_TOKEN" \
  -d '{"name":"Logement","shortDescription":"Parc social","color":"#7A1F2B","icon":"mdi-home-city"}' \
  http://127.0.0.1:8000/api/themes
```

`PUT` et `POST` utilisent `application/ld+json`. `PATCH` utilise `application/merge-patch+json`. Les relations s’écrivent avec l’IRI (`"/api/themes/1"`). Les photos, logos et illustrations restent des fichiers déposés dans EasyAdmin.

En local : `API_TOKEN=` dans `api/.env.local` (modèle : `api/.env.local.example`). En production : [deploy/README.md](deploy/README.md).

## Ressources de l’API

Format de lecture et d’écriture : `application/ld+json`. `PATCH` : `application/merge-patch+json`. Les relations s’écrivent par IRI. Identifiants UUID, sauf les thèmes (entier). Pagination des collections : `?page=` (1-based, 30 éléments). Pas de filtre de recherche ni de tri exposé.

Sans jeton, les fiches `isVisible: false` sont absentes (404 sur l’item, omises des listes). Avec le jeton, le `GET` de la ressource les renvoie. Les listes imbriquées (`sources`, `controversyItems`, `keyFigures`, `people`) ne contiennent que les éléments visibles.

| Ressource | Chemin | Lecture | Écriture (jeton) | Champs principaux |
|-----------|--------|---------|------------------|-------------------|
| Thèmes | `/api/themes` | GET | POST PUT PATCH DELETE | `name` (32), `shortDescription` (128), `color` (16), `icon` (64) |
| Partis | `/api/parties` | GET | POST PUT PATCH DELETE | `name` (64), `color` (16). `logo` en lecture (`/uploads/logos/…`) |
| Personnes | `/api/people` | GET | POST PUT PATCH DELETE | `firstname`, `lastname` (64), `shortDescription` (128), `bio`, `wikiUrl`, `isVisible`, `party` (IRI). `photo` en lecture (`/uploads/photos/…`) |
| Structures | `/api/legal_entities` | GET | POST PUT PATCH DELETE | `name` (64), `shortDescription` (255), `website`, `type` (`Collectivité locale`, `Entreprise`, `Service Public`, `Etablissement Scolaire`). `logo` en lecture |
| Fonctions | `/api/person_legal_entities` | GET | POST PUT PATCH DELETE | `person`, `legalEntity` (IRI), `startDate`, `endDate` (`YYYY-MM-DD`), `position` |
| Promesses | `/api/campaign_promises` | GET | POST PUT PATCH DELETE | `title`, `quote`, `status` (`kept`, `partial`, `broken`, `in_progress`), `resultType` (`absolute`, `percentage`), `resultValue`, `objective`, `resultLabel`, `isVisible`, `theme` (IRI). `illustration` en lecture |
| Affaires | `/api/controversies` | GET | POST PUT PATCH DELETE | `name` (64), `shortDescription` (255), `isVisible`, `theme` (IRI). `illustration` en lecture |
| Faits | `/api/controversy_items` | GET | POST PUT PATCH DELETE | `type` (`fact`, `statement`, `publication`), `title`, `date` (`YYYY-MM-DD`), `datePrecision` (`day`, `month`, `year`), `shortDescription`, `isVisible`, `controversy` (IRI), `people` (IRI) |
| Sources | `/api/sources` | GET | POST PUT PATCH DELETE | `url`, `isVisible`, `controversyItem` ou `campaignPromise` (IRI). `provider` (`link`, `youtube`, `dailymotion`, `vimeo`, `tweet`), titre et vignette sont déduits de l’URL |
| Chiffres clés | `/api/key_figures` | GET | POST PUT PATCH DELETE | `figure` (32), `label` (64), `subLabel`, `priority`, `isVisible`, `controversy` (IRI) |
| Contact | `/api/contacts` | — | POST public | `name`, `email`, `message`, `controversy` (IRI, optionnel). 5 envois par heure et par IP |

`datePrecision` dit ce que signifie `date` sur un fait. `day` est un jour exact (`12 janvier 2019`). `month` veut dire « en mai 2025 » : le jour enregistré est le 1er de ce mois. `year` veut dire « en 2025 » : la date enregistrée est le 1er janvier. Si `datePrecision` est omis à l’écriture, la valeur est `day`. Toute autre valeur est refusée. Le tri des faits reste basé sur `date`. Les dates de fonction (`startDate`, `endDate`) restent des jours exacts : ce ne sont pas des dates d’événement.

Pour un pourcentage, `resultValue` est entre 0 et 100, sans `objective` ni `resultLabel`. Pour un nombre (`absolute`), `resultValue` et `objective` sont requis. Une promesse sans `resultType` n’a aucun de ces trois champs.

## Docker

Les fichiers FrankenPHP et PostgreSQL 17 sont dans `api/`. Docker n’est pas branché sur ce WSL pour l’instant. Quand l’intégration Docker Desktop sera active :

```bash
make docker
```

Le conteneur Postgres publie le port **5434** sur la machine, pour laisser le 5432 au cluster local et le 5433 au cluster Debian. L’API Docker écoute en HTTPS sur le port 443.

## Production (paris-colere.org)

Guide pas à pas (nginx + Docker sur Debian 12) : **[deploy/README.md](deploy/README.md)**.
