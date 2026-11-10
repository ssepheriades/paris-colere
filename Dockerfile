#syntax=docker/dockerfile:1
# Coolify production image: Vue client + Symfony API on FrankenPHP (HTTP only).
# Local Docker Compose continues to use api/Dockerfile — do not change that path for Coolify.

ARG PHP_VERSION=8.4
ARG FRANKENPHP_VERSION=1
ARG NODE_VERSION=22

# -----------------------------------------------------------------------------
# Front (Vue / Vite)
# -----------------------------------------------------------------------------
FROM node:${NODE_VERSION}-bookworm-slim AS client_build

WORKDIR /client
COPY client/package.json client/package-lock.json ./
RUN npm ci
COPY client/ ./
RUN npm run build

# -----------------------------------------------------------------------------
# PHP / FrankenPHP builder
# -----------------------------------------------------------------------------
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION} AS frankenphp_builder

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

WORKDIR /app

# hadolint ignore=DL3008
RUN <<-EOF
	apt-get update
	apt-get install -y --no-install-recommends file git
	install-php-extensions \
		@composer \
		apcu \
		intl \
		opcache \
		pdo_pgsql \
		zip
	rm -rf /var/lib/apt/lists/*
EOF

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/app.conf.d"

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY --link api/frankenphp/conf.d/10-app.ini $PHP_INI_DIR/app.conf.d/
COPY --link api/frankenphp/conf.d/20-app.prod.ini $PHP_INI_DIR/app.conf.d/

COPY --link api/composer.* api/symfony.* ./
RUN composer install --no-cache --prefer-dist --no-dev --no-autoloader --no-scripts --no-progress

COPY --link api/ ./
COPY --from=client_build /client/dist /app/client-dist

# Dummy secret only for build-time cache warmup (runtime uses Coolify env).
ARG APP_SECRET=build-time-placeholder-not-used-in-prod

RUN <<-EOF
	mkdir -p var/cache var/log var/share var/sessions public/uploads/photos public/uploads/logos public/uploads/illustrations
	composer dump-autoload --classmap-authoritative --no-dev
	composer dump-env prod
	composer run-script --no-dev post-install-cmd
	APP_SECRET="${APP_SECRET}" php bin/console cache:warmup --env=prod --no-interaction
	chmod +x bin/console
	chmod -R g=u var public/uploads
	sync
EOF

# Collect shared libraries for a slim runtime image
# hadolint ignore=DL3008,SC3054,DL4006
RUN <<-'EOF'
	apt-get update
	apt-get install -y --no-install-recommends libtree
	mkdir -p /tmp/libs
	BINARIES=(frankenphp php file)
	for target in $(printf '%s\n' "${BINARIES[@]}" | xargs -I{} which {}) \
		$(find "$(php -r 'echo ini_get("extension_dir");')" -maxdepth 2 -name "*.so"); do
		libtree -pv "$target" 2>/dev/null | grep -oP '(?:── )\K/\S+(?= \[)' | while IFS= read -r lib; do
			[ -f "$lib" ] && cp -n "$lib" /tmp/libs/
		done
	done
	rm -rf /var/lib/apt/lists/*
EOF

# -----------------------------------------------------------------------------
# Runtime (non-root, HTTP only — TLS terminated by Coolify/Traefik)
# -----------------------------------------------------------------------------
FROM debian:13-slim AS runtime

SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV PHP_INI_SCAN_DIR=":/usr/local/etc/php/app.conf.d"
ENV SERVER_NAME=:80
ENV CADDY_GLOBAL_OPTIONS="auto_https off"
ENV XDG_CONFIG_HOME=/config
ENV XDG_DATA_HOME=/data
ENV OPENSSL_CONF=/etc/ssl/openssl.cnf
ENV SSL_CERT_FILE=/etc/ssl/certs/ca-certificates.crt

COPY --from=frankenphp_builder /usr/local/bin/frankenphp /usr/local/bin/frankenphp
COPY --from=frankenphp_builder /usr/local/bin/php /usr/local/bin/php
COPY --from=frankenphp_builder /usr/local/bin/docker-php-entrypoint /usr/local/bin/docker-php-entrypoint
COPY --from=frankenphp_builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=frankenphp_builder /tmp/libs /usr/lib

COPY --from=frankenphp_builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d
COPY --from=frankenphp_builder /usr/local/etc/php/php.ini /usr/local/etc/php/php.ini
COPY --from=frankenphp_builder /usr/local/etc/php/app.conf.d /usr/local/etc/php/app.conf.d

COPY --from=frankenphp_builder /etc/ssl/certs/ca-certificates.crt /etc/ssl/certs/ca-certificates.crt
COPY --from=frankenphp_builder /etc/ssl/openssl.cnf /etc/ssl/openssl.cnf
COPY --from=frankenphp_builder /usr/bin/file /usr/bin/file
COPY --from=frankenphp_builder /usr/lib/file/magic.mgc /usr/lib/file/magic.mgc

COPY --link --chmod=755 deploy/coolify/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
COPY --link deploy/coolify/Caddyfile /etc/frankenphp/Caddyfile

RUN <<-EOF
	mkdir -p /data/caddy /config/caddy
	chown -R www-data:www-data /data /config
	find / -perm /6000 -type f -exec chmod a-s {} + 2>/dev/null || true
EOF

COPY --link --exclude=var --from=frankenphp_builder /app /app
COPY --chown=www-data:0 --from=frankenphp_builder /app/var /app/var
RUN chmod g=u /app/var \
	&& mkdir -p /app/public/uploads/photos /app/public/uploads/logos /app/public/uploads/illustrations \
	&& chown -R www-data:0 /app/public/uploads \
	&& chmod -R g=u /app/public/uploads

USER www-data
WORKDIR /app

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
	CMD php -r 'exit(false === @file_get_contents("http://127.0.0.1:80/api", context: stream_context_create(["http" => ["timeout" => 5, "ignore_errors" => true]])) ? 1 : 0);'

ENTRYPOINT ["docker-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
