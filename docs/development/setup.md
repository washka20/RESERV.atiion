# Setup (локально)

## Требования

- Docker + Docker Compose v2
- Git
- Make (рекомендуется)

## Первый запуск

```bash
git clone <repo-url>
cd RESERV.atiion

# Копируем примеры env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env

# Поднимаем сервисы
make up     # или docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Миграции и сиды
make migrate
make seed
```

После этого:
- Customer SPA: http://localhost:5173
- Admin Filament: http://localhost:8080/admin
- API: http://localhost:8080/api/v1
- MinIO console: http://localhost:9001

## Полезные команды

- `make up` / `make down` — запустить / остановить
- `make test` — все тесты
- `make lint` — линтеры
- `make fresh` — пересоздать БД и накатить сиды
- `make logs` — логи контейнеров
- `make shell` — shell в php контейнер

## Credentials (из сидов)

- Admin: `admin@example.com` / `password123` (создаётся в AdminUserSeeder)
- Test user: `test@example.com` / `password123`

## Debugging

- Xdebug включён в dev (см. `docker-compose.dev.yml`)
- Laravel Telescope на `/telescope` (admin only)
- Logs: `make logs`

## Permissions & multi-platform

В dev `php-fpm` работает под `www-data`; его uid/gid remap'ятся под host-юзера автоматически, чтобы bind-mount writes (composer, artisan, storage cache, logs) оставались доступны для `git` / IDE / shell на хосте.

- Makefile вычисляет `APP_UID := $(shell id -u)` и `APP_GID := $(shell id -g)` и экспортирует их.
- `docker-compose.dev.yml` пробрасывает их в `php` build args.
- `docker/php/Dockerfile` dev-stage делает `groupmod`/`usermod` на `www-data`.
- Ручная правка `.env` не требуется.

| Платформа | Поведение |
|-----------|-----------|
| Linux | `id -u/-g` обычно 1000/1000. |
| macOS (Docker Desktop) | `id -u/-g` = 501/20. Флаг `-o` в `groupmod/usermod` допускает клэш с существующим `dialout` (gid 20). |
| Windows WSL2 | Работает как Linux — запускай `make` из WSL shell. |
| Windows PowerShell native | Не поддерживается (`id` недоступен). Использовать WSL2. |

### Когда нужно пересобрать

Docker Compose не отслеживает изменения build-args автоматически. Пересобирать вручную при:

- Первом запуске после клона репо.
- Смене dev-машины (разный uid).
- Изменениях в `docker/php/Dockerfile`.

```bash
make rebuild   # docker compose build --no-cache php
make up
```

### Legacy-файлы с uid=82

Если до фикса крутился контейнер с дефолтным www-data (uid 82) — файлы `backend/storage/`, `backend/bootstrap/cache/`, `backend/vendor/` могут остаться в старом ownership. Одноразовый фикс:

```bash
make fix-perms
```

Делает `chown -R $(APP_UID):$(APP_GID)` внутри контейнера под root.

### Запуск без Make

Если вызываешь `docker compose` напрямую — экспортируй переменные сам:

```bash
export APP_UID=$(id -u) APP_GID=$(id -g)
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Иначе compose подхватит defaults `1000:1000`, что может не совпасть с текущим пользователем.
