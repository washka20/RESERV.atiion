#!/bin/sh
#
# php-fpm entrypoint для dev-контейнера.
#
# Обеспечивает что vendor/ и filament-assets всегда на месте до старта php-fpm.
# Почему: public/{css,fonts,js}/filament/ gitignored (это publishable артефакты,
# не source). Без этого entrypoint при каждом clone / make up / docker compose up
# админка без стилей, потому что filament-публикация не запускается автоматически.
#
# Идемпотентно: если vendor/ уже есть — composer install не запускается;
# если filament-assets уже опубликованы — пропускает шаг.
#
# Зелёный сценарий (второй и последующие старты): ~1 сек overhead.

set -e

cd /var/www/html

# 1. Vendor — первый запуск или nuke вручную
if [ ! -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/autoload.php missing — composer install"
    composer install --no-interaction --prefer-dist --no-progress
fi

# 2. Filament assets (gitignored) — первый запуск, апгрейд Filament, или удалены вручную
if [ ! -d public/css/filament ] || [ -z "$(ls -A public/css/filament 2>/dev/null)" ]; then
    echo "[entrypoint] public/css/filament missing — php artisan filament:assets"
    php artisan filament:assets --no-interaction >/dev/null
    # artisan работает от root (entrypoint без USER), но bind-mount'им в хост
    # под uid 1000/gid 1000 (remapped www-data). Синхронизируем ownership.
    chown -R www-data:www-data public/css/filament public/js/filament public/fonts/filament 2>/dev/null || true
fi

exec "$@"
