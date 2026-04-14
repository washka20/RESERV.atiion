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
