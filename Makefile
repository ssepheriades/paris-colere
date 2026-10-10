.PHONY: serve client db db-start test docker cc dmm

PG_BIN := /usr/lib/postgresql/17/bin
PG_DATA := $(CURDIR)/.data/postgres

serve:
	cd api && symfony serve --port=8000 --no-tls

client:
	cd client && npm run dev -- --port 5173

yb:
	cd client && npm run build

db-start:
	@test -f $(PG_DATA)/PG_VERSION || (echo "Cluster Postgres absent dans .data/postgres" && exit 1)
	@if ! $(PG_BIN)/pg_isready -h 127.0.0.1 -p 5432 -U app >/dev/null 2>&1; then \
		$(PG_BIN)/pg_ctl -D $(PG_DATA) -l $(CURDIR)/.data/postgres.log start; \
	fi

db: db-start
	cd api && php bin/console doctrine:migrations:migrate --no-interaction
	cd api && php bin/console doctrine:fixtures:load --no-interaction --append

test: db-start
	cd api && php bin/console doctrine:migrations:migrate --env=test --no-interaction
	cd api && php bin/phpunit

docker:
	cd api && docker compose up --wait

cc:
	cd api && php bin/console cache:clear

dmm:
	cd api && php bin/console doctrine:migrations:migrate
