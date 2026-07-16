#!/usr/bin/env bash
set -e

# Render provides the port to listen on via $PORT (defaults to 10000).
PORT="${PORT:-10000}"
sed -ri "s!Listen 80!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf

# Cache config/routes, run migrations, link storage (idempotent).
php artisan config:cache || true
php artisan route:cache || true
php artisan migrate --force || true
php artisan storage:link || true

exec apache2-foreground
