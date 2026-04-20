# syntax=docker/dockerfile:1.7

# --- Stage: base (common extensions) ---
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        git \
        curl \
        bash \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        postgresql-dev \
        oniguruma-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        bcmath \
        intl \
        gd \
        zip \
        opcache \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

# --- Stage: dev (xdebug + dev ini) ---
FROM base AS dev

# Remap www-data to host uid/gid for bind-mount write compatibility.
# APP_UID/APP_GID прокидываются Makefile через docker-compose build args (id -u / id -g).
# Флаг -o допускает существующие uid/gid (на alpine gid 20 = dialout, gid 1000 может быть занят).
ARG APP_UID=1000
ARG APP_GID=1000
RUN apk add --no-cache shadow \
    && groupmod -o -g ${APP_GID} www-data \
    && usermod -o -u ${APP_UID} -g ${APP_GID} www-data \
    && apk del shadow

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# В dev код монтируется через bind mount, не копируем.
# entrypoint обеспечивает vendor/ и filament-assets до старта php-fpm.
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# --- Stage: prod (copy code + composer install + optimized autoload) ---
# Prod запускается под www-data (non-root, встроенный в base image) —
# совместимо с FPM pool настройкой в www.conf (user=www-data).
FROM base AS prod

COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/zz-app.ini

# Опкэш-preload для Laravel — ускоряет cold start.
# Файл подключается через php-prod.ini (opcache.preload).
COPY docker/php/opcache-preload.php /usr/local/etc/php/opcache-preload.php

# composer сначала, потом код — кэш слоёв срабатывает при изменениях только код.
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY backend/ ./
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan filament:assets --no-interaction \
    && php artisan storage:link || true \
    && php artisan route:cache \
    && php artisan config:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

# Health: FPM pool ping endpoint + config syntax validation.
# Проверяется snippet'ом из entrypoint (php-fpm -t проверяет config).
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD php-fpm -t 2>&1 | grep -q "test is successful" || exit 1

EXPOSE 9000
CMD ["php-fpm", "-F"]
