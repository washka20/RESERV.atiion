# Docker Compose

Мульти-файловая конфигурация: **base** + один из overrides (**dev / staging / prod**).

## Файлы

| Файл | Назначение |
|------|------------|
| `docker-compose.yml` | Base: все сервисы, сети, volumes. **Без exposed ports**. |
| `docker-compose.dev.yml` | Локальная разработка: bind mounts, exposed ports, Vite, Xdebug. |
| `docker-compose.staging.yml` | Staging: prod-образы, SSL placeholder, limited ports. |
| `docker-compose.prod.yml` | Production: non-root, healthchecks, resource limits, без DB/Redis ports наружу. |

## Как складываются overrides

Docker Compose накладывает override поверх base: значения мёрджатся, списки замещаются. Пример:
```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
```

Команды упакованы в `Makefile`:
- `make up` → dev
- `make staging-up` → staging
- `make prod-up` → prod

## Сервисы

| Сервис | Образ / Build | Порт (dev) | Роль |
|--------|---------------|------------|------|
| `nginx` | `nginx:1.27-alpine` | 8080 | Route `/api` и `/admin` → php, `/` → frontend (dev) или static dist (prod) |
| `php` | `docker/php/Dockerfile` (8.4-FPM) | — | Laravel app, PHP-FPM на 9000 (внутри сети) |
| `frontend` | `docker/node/Dockerfile` | 5273 | Vite dev server (только dev) |
| `postgres` | `postgres:17-alpine` | 5432 | Основная БД |
| `redis` | `redis:7-alpine` | 6379 | Cache / queues / sessions |
| `minio` | `minio/minio:RELEASE.2024-10-13` | 9500 / 9501 | S3-совместимое хранилище |
| `minio-init` | `minio/mc` | — | Создаёт bucket при первом `up`, затем Exit 0 |

## Первый запуск локально

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm php php artisan key:generate
make up
make migrate
```

Открыть:
- http://localhost:8080 — backend + фронт через nginx
- http://localhost:5273 — Vite напрямую
- http://localhost:9501 — MinIO консоль (`minioadmin` / `minioadmin`)

## Порты из переменных окружения

Порты настраиваются через env-переменные с безопасными дефолтами (избегаем конфликтов):

| Переменная | Default | Назначение |
|------------|---------|-----------|
| `NGINX_PORT` | 8080 | Nginx (внешний) |
| `FRONTEND_PORT` | 5273 | Vite (маппируется на 5173 внутри) |
| `POSTGRES_PORT` | 5432 | PostgreSQL |
| `REDIS_PORT` | 6379 | Redis |
| `MINIO_API_PORT` | 9500 | MinIO API (маппируется на 9000 внутри) |
| `MINIO_CONSOLE_PORT` | 9501 | MinIO Console (маппируется на 9001 внутри) |

Для переопределения:
```bash
NGINX_PORT=8888 FRONTEND_PORT=3000 make up
```

## Frontend command

Frontend контейнер выполняет:
```bash
sh -c "npm install && npm run dev -- --host 0.0.0.0"
```

Это гарантирует установку зависимостей при старте и слушание на всех интерфейсах для доступа из хоста.

## Healthchecks

Все сервисы имеют healthchecks. `docker compose ps` показывает `(healthy)` для работающих. `depends_on: condition: service_healthy` гарантирует порядок старта.

## Volumes

- `postgres_data` — данные PG
- `redis_data` — AOF файл Redis
- `minio_data` — объекты MinIO
- `frontend_node_modules` — анонимный в dev, чтобы bind-mount `./frontend` не затирал `node_modules` из образа

## Prod vs Dev ключевые различия

| Аспект | Dev | Prod |
|--------|-----|------|
| Mounts | Bind `./backend`, `./frontend` | Код запечён в образ PHP; фронт — собранный `dist/` volume |
| Xdebug | Включён (триггер) | Нет |
| Ports | Все наружу | Только 80/443 (nginx) |
| User | root внутри php | `app` (non-root) |
| Opcache | validate_timestamps=1 | validate_timestamps=0, JIT |
| Resources | Без лимитов | `deploy.resources.limits` |

## См. также

- [CI/CD](ci-cd.md)
- [Environments](environments.md)
- Spec §13
