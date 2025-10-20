DC_LOCAL=docker compose -f docker-compose.local.yml
DC_PROD=docker compose -f docker-compose.prod.yml

.PHONY: up down restart build migrate cache composer artisan

up:
	${DC_LOCAL} up -d

build:
	UID=$(UID) GID=$(GID) ${DC_LOCAL} down
	UID=$(UID) GID=$(GID) ${DC_LOCAL} build --no-cache
	UID=$(UID) GID=$(GID) ${DC_LOCAL} up -d
	${DC_LOCAL} exec db psql -U postgres -tc "SELECT 1 FROM pg_database WHERE datname='astro'" | grep -q 1 || \
    		${DC_LOCAL} exec db psql -U postgres -c "CREATE DATABASE astro;"
	UID=$(UID) GID=$(GID) ${DC_LOCAL} exec app mkdir -p storage/logs bootstrap/cache storage/framework/{cache,sessions,views,testing}
	UID=$(UID) GID=$(GID) ${DC_LOCAL} exec app composer install
	UID=$(UID) GID=$(GID) ${DC_LOCAL} exec app php artisan key:generate --force
	$(MAKE) migrate

down:
	${DC_LOCAL} down

restart: down up

migrate:
	${DC_LOCAL} exec app php artisan migrate

cache:
	${DC_LOCAL} exec app php artisan optimize:clear

composer-install:
	${DC_LOCAL} exec app composer install

composer-update:
	${DC_LOCAL} exec app composer update

artisan:
	${DC_LOCAL} exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

fix-perms:
	${DC_LOCAL} exec app sh -c "chmod -R 777 storage bootstrap/cache"
