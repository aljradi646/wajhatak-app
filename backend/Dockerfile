# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1: Frontend assets (Vite + Tailwind)
# =============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

# Install JS dependencies (only lockfile first for layer caching)
COPY package.json package-lock.json ./
RUN npm ci

# Copy config + templates + Tailwind content sources.
# resources/views & the Laravel pagination view are scanned by Tailwind so the
# generated CSS contains every utility class actually used by the blades.
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY --from=composer_stage /app/vendor ./vendor
RUN mkdir -p storage/framework/views
RUN npm run build

# =============================================================================
# Stage 2: PHP dependencies (Composer)
# =============================================================================
FROM composer:2 AS composer_stage

WORKDIR /app

# Only composer.json / lockfile are copied here so dependency resolution is
# cached independently from the source code. Vendored deps for PHP 8.2 are
# installed with --no-scripts because the full application (needed for Laravel
# post-autoload scripts like package:discover) is booted later in the runtime.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --ignore-platform-reqs

# =============================================================================
# Stage 3: Final runtime image
# =============================================================================
FROM php:8.2-fpm-alpine AS runtime

# Install runtime PHP extensions and the Caddy web server.
# - Caddy serves as a reverse proxy to php-fpm and provides automatic HTTPS.
# - install-php-extensions is the easiest way to add all required extensions
#   in one shot on Alpine.
RUN apk add --no-cache \
        curl \
        unzip \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        libxml2-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        mysqli \
        mbstring \
        zip \
        intl \
        bcmath \
        exif \
        pcntl \
        opcache \
        gd \
    && docker-php-ext-enable opcache

# Install Caddy (static binary) for a zero-friction, HTTPS-by-default web server
RUN curl -fsSL "https://caddyserver.com/api/download?os=linux&arch=amd64" -o /usr/local/bin/caddy \
    && chmod +x /usr/local/bin/caddy \
    && caddy version

# Working directory
WORKDIR /var/www/html

# Copy application source (respecting .dockerignore)
COPY . .

# Copy the built frontend assets from Stage 1
COPY --from=frontend /app/public/build ./public/build

# Copy Composer dependencies from Stage 2 (into the same path)
COPY --from=composer_stage /app/vendor ./vendor

# Configuration files for the web server and bootstrap
COPY Caddyfile /etc/caddy/Caddyfile
COPY entrypoint.sh /usr/local/bin/entrypoint

# Regenerate the optimized autoloader against the real PHP runtime and let
# Laravel discover package service providers (writes bootstrap/cache/packages.php).
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi || true

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/app/public storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

# Production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "upload_max_filesize=20M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "post_max_size=25M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "max_execution_time=120" >> "$PHP_INI_DIR/conf.d/99-app.ini"

# OPCache tuned for production
ADD ./php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Caddy listens on the port injected by Railway ($PORT, default 8080 locally)
EXPOSE 8080

# The entrypoint runs migrations/seeds/caches and then starts PHP-FPM.
# Queue/scheduler are handled by separate Railway services (RAILWAY_SERVICE_TYPE).
ENTRYPOINT ["/usr/local/bin/entrypoint"]
