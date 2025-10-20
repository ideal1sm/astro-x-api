FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git zip unzip libpq-dev libonig-dev libzip-dev libicu-dev \
    && docker-php-ext-install intl pdo pdo_pgsql mbstring zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ARG UID=1000
ARG GID=1000
RUN addgroup --gid $GID appuser \
    && adduser --disabled-password --gecos "" --uid $UID --gid $GID appuser

RUN mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R appuser:appuser /var/www/html

USER appuser

WORKDIR /var/www/html
