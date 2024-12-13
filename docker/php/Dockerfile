FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends git vim zip zlib1g-dev libzip-dev && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY ./docker/php/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
COPY . /app

RUN composer install --no-interaction --prefer-dist --optimize-autoloader  && composer config --no-plugins allow-plugins.infection/extension-installer true