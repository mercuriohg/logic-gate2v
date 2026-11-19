FROM composer:2 AS dependencies

WORKDIR /app
COPY composer.json ./
COPY composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

FROM php:8.2-apache

COPY --from=dependencies /app/vendor /var/www/vendor
RUN docker-php-ext-install pdo_mysql

RUN a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html