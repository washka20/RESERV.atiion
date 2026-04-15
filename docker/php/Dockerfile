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

# В dev код монтируется через bind mount, не копируем
CMD ["php-fpm"]

# --- Stage: prod (copy code + composer install) ---
FROM base AS prod

COPY docker/php/php-prod.ini /usr/local/etc/php/conf.d/zz-app.ini

# Non-root user
RUN addgroup -g 1000 app \
    && adduser -u 1000 -G app -s /bin/sh -D app

COPY --chown=app:app backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY --chown=app:app backend/ ./
RUN composer dump-autoload --optimize --classmap-authoritative \
    && php artisan storage:link || true

USER app

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php-fpm -t || exit 1

CMD ["php-fpm"]
