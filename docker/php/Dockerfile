FROM php:8.3-fpm

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN apt-get update
RUN apt-get install -y git zip zlib1g-dev libzip-dev zip vim && docker-php-ext-install zip
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY ./docker/php/xdebug.ini $PHP_INI_DIR/php.ini

WORKDIR /app
   