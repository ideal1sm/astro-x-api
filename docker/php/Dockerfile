# syntax=docker/dockerfile:1

# ============ Base Stage ============
FROM php:8.3-fpm AS base

RUN apt-get update && apt-get install -y \
    git zip unzip libpq-dev libonig-dev libzip-dev libicu-dev \
    && docker-php-ext-install intl pdo pdo_pgsql mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ============ Dev Stage ============
FROM base AS dev

ARG UID=1000
ARG GID=1000

RUN addgroup --gid $GID appuser \
    && adduser --disabled-password --gecos "" --uid $UID --gid $GID appuser \
    && mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R appuser:appuser /var/www/html

USER appuser

CMD ["php-fpm"]

# ============ Prod Stage ============
FROM base AS prod

WORKDIR /var/www/html

# копируем файлы проекта
COPY . .

# устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 storage bootstrap/cache

USER www-data

CMD ["php-fpm"]
