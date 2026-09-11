FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache postgresql-dev icu-dev zlib-dev libzip-dev rabbitmq-c-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql intl zip opcache \
    && yes '' | pecl install amqp \
    && docker-php-ext-enable amqp \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize


FROM php:8.4-fpm-alpine

# rabbitmq-c is the runtime library the amqp extension (built in the builder stage) links against.
RUN apk add --no-cache nginx supervisor postgresql-libs icu-libs libzip rabbitmq-c

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
