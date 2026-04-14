# Environments

Три среды: **dev** (локально), **staging**, **prod**. Отличия описаны в `.env.*` и `docker-compose.*.yml` overrides.

## Шаблоны env

- `backend/.env.example` — backend
- `frontend/.env.example` — frontend

## Переменные окружения

### Backend

| Переменная | Dev | Staging | Prod | Назначение |
|------------|-----|---------|------|------------|
| `APP_ENV` | `local` | `staging` | `production` | Laravel environment |
| `APP_DEBUG` | `true` | `false` | `false` | Stack traces в ответах |
| `APP_URL` | `http://localhost:8080` | `https://staging.reservatiion.xxx` | `https://reservatiion.xxx` | Base URL |
| `APP_KEY` | generate | secret manager | secret manager | Laravel encryption key |
| `DB_HOST` | `postgres` | `postgres` | managed PG host | БД хост |
| `DB_DATABASE` | `reservatiion` | `reservatiion_staging` | `reservatiion_prod` | БД имя |
| `DB_USERNAME` / `DB_PASSWORD` | `app` / `secret` | secrets | secrets | БД creds |
| `REDIS_HOST` | `redis` | `redis` | managed Redis | Redis хост |
| `CACHE_STORE` | `redis` | `redis` | `redis` | Cache driver |
| `QUEUE_CONNECTION` | `redis` | `redis` | `redis` | Queue driver |
| `SESSION_DRIVER` | `redis` | `redis` | `redis` | Session store (для Filament admin) |
| `FILESYSTEM_DISK` | `s3` | `s3` | `s3` | Default disk |
| `AWS_ENDPOINT` | `http://minio:9000` | MinIO staging URL | AWS/Yandex S3 URL | S3 endpoint |
| `AWS_BUCKET` | `reservatiion` | `reservatiion-staging` | `reservatiion-prod` | Bucket |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` | `true` (MinIO) | `false` (AWS) | Path-style addressing |
| `JWT_SECRET` | generate | secret manager | secret manager | JWT подпись (см. Plan 4) |
| `JWT_TTL` / `JWT_REFRESH_TTL` | 60 / 43200 | 60 / 43200 | 60 / 43200 | TTL в минутах |
| `LOG_LEVEL` | `debug` | `info` | `warning` | Laravel log threshold |
| `MAIL_MAILER` | `log` | `smtp` | `smtp` | Mail transport |

### Frontend

| Переменная | Dev | Staging | Prod |
|------------|-----|---------|------|
| `VITE_API_BASE_URL` | `http://localhost:8080/api/v1` | `https://staging.reservatiion.xxx/api/v1` | `https://reservatiion.xxx/api/v1` |
| `VITE_APP_NAME` | `RESERV.atiion` | `RESERV.atiion (staging)` | `RESERV.atiion` |

### Docker Compose

Порты настраиваются через env-переменные, чтобы избежать конфликтов при локальной разработке:

| Переменная | Default | Назначение |
|------------|---------|-----------|
| `NGINX_PORT` | 8080 | Nginx (внешний) |
| `FRONTEND_PORT` | 5273 | Vite (маппируется на 5173 внутри контейнера) |
| `POSTGRES_PORT` | 5432 | PostgreSQL |
| `REDIS_PORT` | 6379 | Redis |
| `MINIO_API_PORT` | 9500 | MinIO API (маппируется на 9000 внутри контейнера) |
| `MINIO_CONSOLE_PORT` | 9501 | MinIO Console (маппируется на 9001 внутри контейнера) |
| `XDEBUG_MODE` | `off` | Xdebug trigger mode для PHP (только dev) |

Переменные читаются из `.env` файла или из shell переменных при запуске.

Для переопределения портов:
```bash
NGINX_PORT=8888 FRONTEND_PORT=3000 make up
```

## Секреты

- **Dev:** значения в `backend/.env` (gitignored)
- **Staging/Prod:** injection через CI/CD environment secrets или secret manager (AWS SM / HashiCorp Vault / Yandex Lockbox). **Не коммитить.**

## Переключение среды

```bash
make up          # dev
make staging-up  # staging
make prod-up     # prod
```

## См. также

- [Docker](docker.md)
- Spec §13
