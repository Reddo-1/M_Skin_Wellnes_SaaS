FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql zip bcmath gd intl \
    && apt-get clean

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ARG APP_ENV=production

COPY ./M_Skin_Welness_BE/composer.json ./M_Skin_Welness_BE/composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist --no-scripts \
    && composer clear-cache
