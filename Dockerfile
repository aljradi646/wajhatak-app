# syntax=docker/dockerfile:1

# =============================================================================
# Wajhatak backend - production Docker image (repository-root variant)
#
# Build context = repository root. mobile/ and docs are excluded via the root
# .dockerignore, and every source path below is prefixed with `backend/`.
# Railway uses this file via the root railway.toml (builder = DOCKERFILE).
#
# Stages:
#   1. Frontend  : node:22-alpine -> Vite + Tailwind build into public/build
#   2. Deps      : composer:2     -> PHP deps (--no-scripts; booted at runtime)
#   3. Runtime   : php:8.2-fpm-alpine + Caddy -> serves Laravel on $PORT
# =============================================================================

# --- Stage 1: Frontend assets (Vite + Tailwind) ---------------------------
FROM node:22-alpine AS frontend

WORKDIR /app

COPY backend/package.json backend/package-lock.json ./
RUN npm ci

# Tailwind scans resources/views and the Laravel pagination view so the
# generated CSS contains every utility class actually used by the blades.
COPY backend/vite.config.js backend/tailwind.config.js backend/postcss.config.js ./
COPY backend/resources ./resources
COPY --from=composer_stage /app/vendor ./vendor
RUN mkdir -p storage/framework/views
RUN npm run build

# --- Stage 2: PHP dependencies (Composer) ---------------------------------
FROM composer:2 AS composer_stage

WORKDIR /app

# Only composer.json / lockfile here so dependency resolution is cached
# independently from source. --no-scripts because Laravel needs the full app
# (package:discover) which runs later in the runtime stage.
COPY backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --ignore-platform-reqs

# --- Stage 3: Final runtime image -----------------------------------------
FROM php:8.2-fpm-alpine AS runtime

# PHP extensions required by Laravel + the Caddy web server (reverse proxy to
# php-fpm, HTTPS by default via Railway's public domain).
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

# Install Caddy (static binary)
RUN curl -fsSL "https://caddyserver.com/api/download?os=linux&arch=amd64" -o /usr/local/bin/caddy \
    && chmod +x /usr/local/bin/caddy \
    && caddy version

WORKDIR /var/www/html

# Application source (only tracked backend files; root .dockerignore prunes
# local junk, caches and the mobile/docs folders).
COPY backend/ /var/www/html/

# Built frontend assets + PHP dependencies
COPY --from=frontend /app/public/build ./public/build
COPY --from=composer_stage /app/vendor ./vendor

# Web server config + bootstrap script
COPY backend/Caddyfile /etc/caddy/Caddyfile
COPY backend/entrypoint.sh /usr/local/bin/entrypoint

# Prepare writable directories first, then regenerate the optimized autoloader
# and let Laravel discover package service providers.
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/app/public storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi || true

# Production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "memory_limit=256M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "upload_max_filesize=20M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "post_max_size=25M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "max_execution_time=120" >> "$PHP_INI_DIR/conf.d/99-app.ini"

# OPCache tuned for production
COPY backend/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Caddy listens on the port injected by Railway ($PORT, default 8080 locally)
EXPOSE 8080

# Entrypoint runs migrations/seeds/caches then starts PHP-FPM + Caddy.
# Queue/scheduler are separate Railway services (RAILWAY_SERVICE_TYPE).
ENTRYPOINT ["/usr/local/bin/entrypoint"]