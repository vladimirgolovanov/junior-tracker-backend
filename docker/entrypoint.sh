#!/bin/sh
set -e

php bin/console cache:warmup --env=prod
chown -R www-data:www-data var/cache var/log

exec supervisord -c /etc/supervisord.conf
