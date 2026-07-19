FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache postgresql-dev icu-dev zlib-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize


FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx supervisor postgresql-libs icu-libs libzip

COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

WORKDIR /var/www/html

COPY --from=builder /var/www/html ./

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/php.ini
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

RUN mkdir -p var/cache var/log && chown -R www-data:www-data var

ENTRYPOINT ["/entrypoint.sh"]
