#!/usr/bin/env bash
set -e

# Render provides the port to listen on via $PORT (defaults to 10000).
PORT="${PORT:-10000}"
sed -ri "s!Listen 80!Listen ${PORT}!g" /etc/apache2/ports.conf
sed -ri "s!<VirtualHost \*:80>!<VirtualHost *:${PORT}>!g" /etc/apache2/sites-available/000-default.conf

# Render mounts Secret Files readable by root only, but Apache runs PHP as
# www-data. Copy the DB CA cert somewhere www-data can read it and point the
# config at the copy BEFORE the config cache is built (the path gets baked in).
if [ -n "${MYSQL_ATTR_SSL_CA:-}" ] && [ -f "$MYSQL_ATTR_SSL_CA" ]; then
  cp "$MYSQL_ATTR_SSL_CA" /var/www/html/db-ca.pem
  chmod 644 /var/www/html/db-ca.pem
  export MYSQL_ATTR_SSL_CA=/var/www/html/db-ca.pem
fi

# Cache config/routes, run migrations, link storage (idempotent).
php artisan config:cache || true
php artisan route:cache || true
# One-time clean rebuild: set DB_FRESH_MIGRATE=true to drop all tables and
# re-run every migration from scratch. Set it back to false after the first
# successful boot so later restarts don't wipe the database.
if [ "${DB_FRESH_MIGRATE:-false}" = "true" ]; then
  php artisan migrate:fresh --force || true
else
  php artisan migrate --force || true
fi
# Create the permanent owner account from SUPER_ADMIN_* (idempotent — skips if it exists).
php artisan db:seed --class=SuperAdminSeeder --force || true
php artisan storage:link || true

# The artisan commands above ran as root and may have created files in
# storage/ and bootstrap/cache/ that www-data (Apache) can't write to.
chown -R www-data:www-data storage bootstrap/cache || true

# Persistent background queue worker, running for the container's whole
# lifetime — independent of any single HTTP request. This container runs
# Apache + mod_php (no PHP-FPM), so there is no way to "finish the response
# early and keep working" for a single request; a real worker process is the
# only reliable way to send order emails without blocking checkout on slow
# SMTP. Requires QUEUE_CONNECTION=database (a sync queue would just run jobs
# inline again). The retry loop restarts the worker if it ever crashes.
if [ "${QUEUE_CONNECTION:-sync}" = "database" ]; then
  (
    while true; do
      su -s /bin/bash www-data -c "cd /var/www/html && php artisan queue:work --queue=default --tries=1 --timeout=60 --sleep=3" || true
      sleep 2
    done
  ) &
fi

exec apache2-foreground
