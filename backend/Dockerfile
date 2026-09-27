# syntax=docker/dockerfile:1

# =============================================================================
# Stage 0: llama.cpp (static llama-server) — محرك الاستدلال المحلي للمساعد الذكي
#
# بناء ثابت (static musl) يعمل على أي alpine بدون مكتبات إضافية. يقدّم
# OpenAI-compatible API (/v1/chat/completions + /v1/models) وهو ما يعتمده
# LocalGlmProvider حرفيًا. الخدمة تُدار من entrypoint.sh بنفس نمط عامل الطابور.
# =============================================================================
FROM alpine:3.21 AS llama-builder

RUN apk add --no-cache build-base cmake git

RUN git clone --depth 1 https://github.com/ggml-org/llama.cpp /src

# بناء llama-server فقط (بدون اختبارات/أمثلة) + ثابت + بلا OpenMP لضمان التوافق.
RUN cmake -S /src -B /build \
        -DCMAKE_BUILD_TYPE=Release \
        -DGGML_NATIVE=OFF \
        -DGGML_OPENMP=OFF \
        -DLLAMA_CURL=OFF \
        -DLLAMA_BUILD_TESTS=OFF \
        -DLLAMA_BUILD_EXAMPLES=OFF \
        -DLLAMA_BUILD_SERVER=ON \
        -DCMAKE_EXE_LINKER_FLAGS="-static -s" \
    && cmake --build /build -j"$(nproc)" --target llama-server

# =============================================================================
# Stage 1: PHP dependencies (Composer)
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
        --ignore-platform-reqs \
    && if [ -d vendor/laravel/pail ]; then rm -rf vendor/laravel/pail; fi

# =============================================================================
# Stage 2: Frontend assets (Vite + Tailwind)
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
# Stage 3: Final runtime image
# =============================================================================
FROM php:8.4-fpm-alpine AS runtime

# Install runtime PHP extensions and the Caddy web server.
# - Caddy serves as a reverse proxy to php-fpm and provides automatic HTTPS.
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

# Composer is needed at runtime only to refresh vendor on first boot if the image
# was rebuilt from an old cache or if vendor was pruned from the image.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Working directory
WORKDIR /var/www/html

# Copy application source (respecting .dockerignore)
COPY . .

# Copy the built frontend assets from Stage 2
COPY --from=frontend /app/public/build ./public/build

# Copy Composer dependencies from Stage 1 (into the same path)
COPY --from=composer_stage /app/vendor ./vendor

# Configuration files for the web server and bootstrap
COPY Caddyfile /etc/caddy/Caddyfile
COPY entrypoint.sh /usr/local/bin/entrypoint

# محرك الاستدلال المحلي للمساعد الذكي (ثابت — لا يحتاج مكتبات وقت التشغيل)
COPY --from=llama-builder /build/bin/llama-server /usr/local/bin/llama-server
RUN chmod +x /usr/local/bin/llama-server && llama-server --version 2>/dev/null || true

# Prepare writable directories first, then regenerate the optimized autoloader
# and let Laravel discover package service providers.
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p bootstrap/cache storage/framework/{cache,sessions,views} storage/app/public storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

RUN rm -rf vendor/laravel/pail 2>/dev/null || true \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && php artisan package:discover --ansi || true

# Production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN echo "memory_limit=384M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "upload_max_filesize=20M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "post_max_size=25M" >> "$PHP_INI_DIR/conf.d/99-app.ini" \
    && echo "max_execution_time=120" >> "$PHP_INI_DIR/conf.d/99-app.ini"

# OPCache tuned for production
COPY php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Caddy listens on the port injected by Railway ($PORT, default 8080 locally)
EXPOSE 8080

# The entrypoint runs migrations/seeds/caches and then starts PHP-FPM.
# Queue/scheduler are handled by separate Railway services (RAILWAY_SERVICE_TYPE).
ENTRYPOINT ["/usr/local/bin/entrypoint"]