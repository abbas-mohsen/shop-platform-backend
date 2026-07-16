# Laravel 8 backend for Render (free web service).
# Serves the app with Apache, document root at /public.
FROM php:8.1-apache

# --- System packages + PHP extensions ---
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring bcmath gd zip exif \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# --- Composer ---
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies (plus the S3 adapter for Cloudflare R2 image storage).
COPY composer.json composer.lock ./
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && composer require league/flysystem-aws-s3-v3 "^1.0" --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache

# Apache vhost (public root + .htaccess) and startup script.
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
