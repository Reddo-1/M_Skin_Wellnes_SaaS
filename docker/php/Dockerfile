FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ARG APP_ENV=production

COPY ./M_Skin_Welness_BE/composer.json ./M_Skin_Welness_BE/composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist \
    && composer clear-cache
