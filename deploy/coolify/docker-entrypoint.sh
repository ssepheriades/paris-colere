#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
	php bin/console -V

	if [ -n "${DATABASE_URL:-}" ]; then
		echo 'Waiting for database to be ready...'
		ATTEMPTS_LEFT_TO_REACH_DATABASE=60
		until [ "$ATTEMPTS_LEFT_TO_REACH_DATABASE" -eq 0 ]; do
			if php -r '
				$url = getenv("DATABASE_URL") ?: "";
				$p = parse_url($url);
				if (!$p || empty($p["host"]) || empty($p["path"])) {
					fwrite(STDERR, "Invalid DATABASE_URL\n");
					exit(1);
				}
				$db = ltrim(explode("?", $p["path"], 2)[0], "/");
				$dsn = sprintf(
					"pgsql:host=%s;port=%d;dbname=%s",
					$p["host"],
					$p["port"] ?? 5432,
					$db
				);
				$pdo = new PDO($dsn, $p["user"] ?? null, $p["pass"] ?? null, [
					PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
					PDO::ATTR_TIMEOUT => 3,
				]);
				$pdo->query("SELECT 1");
			' 2>/tmp/db-wait.err; then
				break
			fi
			ATTEMPTS_LEFT_TO_REACH_DATABASE=$((ATTEMPTS_LEFT_TO_REACH_DATABASE - 1))
			echo "Still waiting for database... $ATTEMPTS_LEFT_TO_REACH_DATABASE attempts left."
			sleep 1
		done

		if [ "$ATTEMPTS_LEFT_TO_REACH_DATABASE" -eq 0 ]; then
			echo 'The database is not up or not reachable:'
			cat /tmp/db-wait.err 2>/dev/null || true
			exit 1
		fi

		echo 'Database ready — running Doctrine migrations (idempotent).'
		php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing
	else
		echo 'DATABASE_URL is empty — skipping migrations.' >&2
	fi

	echo 'PHP app ready!'
fi

exec docker-php-entrypoint "$@"
