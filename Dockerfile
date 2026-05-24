# ── Stage 1: build front-end assets ──────────────────────────────────────────
FROM node:22-alpine AS assets

ENV PNPM_HOME="/pnpm"
ENV PATH="$PNPM_HOME:$PATH"
RUN corepack enable && corepack prepare pnpm@latest --activate

WORKDIR /app

COPY package.json pnpm-lock.yaml ./
RUN pnpm install --frozen-lockfile

COPY resources/ resources/
COPY public/    public/
COPY vite.config.js ./

RUN pnpm run build

# ── Stage 2: PHP dependencies ─────────────────────────────────────────────────
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# ── Stage 3: final image ───────────────────────────────────────────────────────
FROM dunglas/frankenphp:php8.4-alpine

# PHP extensions required by Laravel + Octane
RUN install-php-extensions \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
        redis \
        zip \
        bcmath \
        intl \
        opcache

WORKDIR /app

# Application source (vendor excluded via .dockerignore)
COPY --chown=www-data:www-data . .

# Built Vite assets
COPY --from=assets  --chown=www-data:www-data /app/public/build ./public/build

# Composer vendor (includes laravel/octane)
COPY --from=vendor  --chown=www-data:www-data /app/vendor ./vendor

# Octane reads the Caddyfile from the project root
COPY --chown=www-data:www-data docker/Caddyfile ./Caddyfile

COPY --chmod=755 docker/entrypoint.sh /entrypoint.sh

# Writable directories
RUN mkdir -p storage/logs \
             storage/framework/cache \
             storage/framework/sessions \
             storage/framework/views \
             bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Discover packages (soft-fail: APP_KEY may not be set at build time)
RUN php artisan package:discover --ansi 2>/dev/null || true

EXPOSE 80 443

USER www-data

ENTRYPOINT ["/entrypoint.sh"]

CMD ["php", "artisan", "octane:start", \
     "--server=frankenphp", \
     "--host=0.0.0.0", \
     "--port=80", \
     "--admin-port=2019", \
     "--workers=auto", \
     "--max-requests=500"]
