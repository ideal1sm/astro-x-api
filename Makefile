DC_LOCAL=docker compose -f docker-compose.local.yml
DC_PROD=docker compose -f docker-compose.prod.yml

.PHONY: up down restart build migrate cache composer artisan fix-perms \
        dev-up dev-build dev-down dev-restart dev-migrate dev-cache dev-composer-install dev-composer-update dev-artisan dev-fix-perms \
        prod-up prod-build prod-down prod-restart prod-migrate prod-cache prod-artisan

# ===============================
# ========== DEV =================
# ===============================

dev-up:
	${DC_LOCAL} up -d

dev-build:
	UID=$(UID) GID=$(GID) ${DC_LOCAL} down
	UID=$(UID) GID=$(GID) ${DC_LOCAL} build --no-cache
	UID=$(UID) GID=$(GID) ${DC_LOCAL} up -d
	${DC_LOCAL} exec db psql -U postgres -tc "SELECT 1 FROM pg_database WHERE datname='astro'" | grep -q 1 || \
    		${DC_LOCAL} exec db psql -U postgres -c "CREATE DATABASE astro;"
	UID=$(UID) GID=$(GID) ${DC_LOCAL} exec app mkdir -p storage/logs bootstrap/cache storage/framework/{cache,sessions,views,testing}
	UID=$(UID) GID=$(GID) ${DC_LOCAL} exec app composer install
	UID=$(UID) GID=$(GID) ${DC_LOCAL} exec app php artisan key:generate --force
	$(MAKE) dev-migrate

dev-down:
	${DC_LOCAL} down

dev-restart: dev-down dev-up

dev-migrate:
	${DC_LOCAL} exec app php artisan migrate

dev-cache:
	${DC_LOCAL} exec app php artisan optimize:clear

dev-composer-install:
	${DC_LOCAL} exec app composer install

dev-composer-update:
	${DC_LOCAL} exec app composer update

dev-artisan:
	${DC_LOCAL} exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

dev-fix-perms:
	${DC_LOCAL} exec app sh -c "chmod -R 777 storage bootstrap/cache"


# ===============================
# ========== PROD ================
# ===============================

prod-build:
	${DC_PROD} build --no-cache

prod-up:
	${DC_PROD} up -d

prod-down:
	${DC_PROD} down

prod-restart: prod-down prod-up

prod-migrate:
	${DC_PROD} exec app php artisan migrate --force

prod-cache:
	${DC_PROD} exec app php artisan optimize:clear

prod-artisan:
	${DC_PROD} exec app php artisan $(filter-out $@,$(MAKECMDGOALS))
