# Production Deployment Runbook

Первый запуск production сервера + последующие обновления.

## Pre-flight checklist

- [ ] VPS с Ubuntu 22.04 / Debian 12, 4 vCPU, 4-8 GB RAM, 40+ GB SSD
- [ ] DNS A-record указывает на IP VPS
- [ ] SSH ключ на сервере (не password auth)
- [ ] `ufw allow 22/tcp && ufw allow 80/tcp && ufw allow 443/tcp && ufw enable`
- [ ] Non-root sudo user (`adduser deploy && usermod -aG sudo deploy`)
- [ ] fail2ban installed

## First deploy

### 1. Install Docker

```bash
curl -fsSL https://get.docker.com | sh
usermod -aG docker deploy
```

### 2. Clone repo

```bash
su - deploy
git clone git@github.com:washka20/RESERV.atiion.git /srv/reservatiion
cd /srv/reservatiion
```

### 3. Create production `.env`

```bash
cp backend/.env.example backend/.env.prod
# Отредактировать backend/.env.prod:
# - APP_KEY (сгенерировать: docker compose run --rm php php artisan key:generate --show)
# - APP_URL=https://<domain>
# - DB_PASSWORD=<strong-password>
# - JWT_SECRET=<random 64 chars>
# - AWS_BUCKET + AWS_ENDPOINT (S3 prod)
# - MEDIA_SIGNED_URL_TTL_MIN=60
vim backend/.env.prod
ln -s .env.prod backend/.env
```

### 4. SSL certs (Let's Encrypt)

```bash
apt install certbot
certbot certonly --standalone -d <domain>
# Certs в /etc/letsencrypt/live/<domain>/
# Раскомментировать 443 блок в docker/nginx/prod.conf + подставить paths
vim docker/nginx/prod.conf
```

### 5. Build + run

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml build
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Wait for healthchecks
docker compose ps  # все healthy?

# Migrate
docker compose exec php php artisan migrate --force
docker compose exec php php artisan db:seed --force  # если нужны default roles

# Optimize
docker compose exec php php artisan optimize
```

### 6. Smoke test

```bash
curl https://<domain>/api/v1/health
# Должен вернуть {"status":"healthy","checks":{...}}
```

## Subsequent deploys (rolling update)

```bash
cd /srv/reservatiion
git pull origin main

# Rebuild image (быстро, кэш слоёв)
docker compose -f docker-compose.yml -f docker-compose.prod.yml build php nginx

# Migrate ДО restart (backward-compatible migrations!)
docker compose exec php php artisan migrate --force

# Restart только изменённые сервисы (php + nginx ↔ frontend)
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps php nginx

# Проверить
curl https://<domain>/api/v1/health
docker compose logs -f php --tail 100  # убедиться что нет ошибок
```

**Downtime:** ~10-30s пока php контейнер рестартует. Zero-downtime требует blue-green (Phase 2).

## Rollback

Если свежий deploy сломался:

```bash
cd /srv/reservatiion
git log --oneline -5  # найти previous good SHA
git checkout <prev-sha>
docker compose -f docker-compose.yml -f docker-compose.prod.yml build php nginx
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps php nginx

# Если миграция сломала БД — восстановить из backup
# См. docs/deployment/backup.md (Phase 2)
```

**Автоматизация** — `scripts/deploy.sh` + `scripts/rollback.sh` в Phase 2.

## Мониторинг

Пока базовый:
- `docker stats` — CPU/mem usage
- `docker compose logs --tail 100` — runtime errors
- `curl /api/v1/health` — состояние dependencies
- `docker compose ps` — healthy / unhealthy

**Full observability** (Sentry, Prometheus, structured logs) — Plan 11 Phase 2.

## Критичные gotchas

1. **Не делать breaking миграции** — всегда backward-compatible (add column nullable, потом backfill, потом NOT NULL в отдельном deploy)
2. **Не менять JWT_SECRET после deploy** — все активные tokens invalidate, users получат 401
3. **`.env.prod` не в git** — хранить на сервере + backup в secure location
4. **SSL certs auto-renew** — crontab: `0 3 * * * certbot renew --quiet && docker compose exec nginx nginx -s reload`
5. **Backup БД перед каждой миграцией** — `docker compose exec postgres pg_dump -U app reservatiion > backup-$(date +%Y%m%d-%H%M%S).sql`

## References

- [ADR-018 Deployment strategy](../adr/018-deployment-strategy.md)
- [Security hardening checklist](security.md)
- [S3 / MinIO setup](s3-setup.md)
