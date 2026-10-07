#!/usr/bin/env sh
set -eu

sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf

php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
