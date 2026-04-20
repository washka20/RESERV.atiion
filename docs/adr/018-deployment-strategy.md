# ADR 018: Deployment strategy — SSH + single-server для MVP

**Status:** Accepted
**Date:** 2026-04-20

## Context

Plan 11 Phase 1 — production-ready артефакты без лишней сложности:
- Docker images с opcache + JIT + non-root
- nginx с security headers + rate limiting
- Resource limits на каждый сервис
- Health endpoint + logging rotation

Вопрос: как deploy'ить — Kubernetes, ECS, SSH single-server или serverless?

## Decision

### 1. **SSH-based single-server deploy для MVP**

Один VPS с Docker + Docker Compose. Deploy через SSH:
```bash
ssh prod-host
cd /srv/reservatiion
git pull origin main
docker compose -f docker-compose.yml -f docker-compose.prod.yml pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --no-deps php nginx
docker compose exec php php artisan migrate --force
```

**Почему SSH, не k8s:**
- MVP: 1 app, 1 БД, <10k DAU на старте. k8s overhead (etcd, control plane, ingress) не окупается.
- Debugging: `ssh prod && docker logs php` быстрее чем `kubectl logs`
- Cost: $10-40/mo VPS vs $70+/mo EKS minimum
- Учить k8s → отдельный цикл, отложен до второй ноды

### 2. **Registry-based image pull, не rebuild на сервере**

CI строит image → `ghcr.io/washka20/reservatiion/php:sha-<gitsha>` → deploy pull'ит.

**Почему:**
- Deployment детерминирован (image hash == code state)
- Откат = `docker pull <prev-tag> && up -d`
- Не грузим prod сервер композитом сборки (gcc, composer, npm)
- CI cache даёт reproducible builds

### 3. **Non-root everywhere**

- `php` контейнер: `USER www-data`
- `nginx` контейнер: дефолт из alpine image — nginx user
- Никаких `USER root` в прод-стадии (dev target можно)

### 4. **БД/Redis/MinIO не exposed в prod**

В `docker-compose.prod.yml` `ports: []` для postgres/redis/minio. Доступ только через docker network между сервисами. Снаружи — только 80/443 через nginx.

### 5. **Logging rotation hard cap**

`json-file` driver с `max-size: 10m` + `max-file: 3` = **30 MB hard cap per service**. Избегаем disk fill при run-loop ошибках. Collectable через `docker logs` или forward в Sentry/CloudWatch (Phase 2).

### 6. **Opcache + JIT обязательно**

Без opcache latency **×5-10**. JIT в tracing mode даёт доп ~15-25% throughput на CPU-bound запросах. Preload файл включает Laravel + Carbon в shared memory.

### 7. **SSL через Let's Encrypt bind-mount**

nginx config — placeholder для `443 ssl http2;` блока. Оператор раскомментирует после первого `certbot certonly`. Volume mount `/etc/letsencrypt` в nginx контейнер.

Автообновление certs — cron на хосте: `certbot renew && docker compose exec nginx nginx -s reload`.

## Consequences

**Плюсы:**
- Стартуем быстро и дёшево
- Debug flow понятный любому backend-разработчику
- Image-based rollback в 1 команду
- Security baseline (non-root, no exposed DB, headers, rate limits)

**Минусы:**
- Single point of failure — одна VM падает, сервис лежит
- Нет auto-scaling (можно ручной через `deploy.replicas`, но без LB между VM)
- Zero-downtime deploy требует blue-green скрипт — пока нет (оффчас ~30s)
- Монолитный сервер: tooling (k8s + Helm) не развиваем — миграция позже будет тяжелее

## Когда мигрировать на k8s

Критерии:
- DAU > 50k стабильно → нужен horizontal scaling
- Uptime SLA > 99.5% → нужна multi-AZ
- Compliance требует audit trail / RBAC на infra
- Несколько приложений делят инфраструктуру

До этих маркеров — SSH + single-server реально достаточно.

## Alternatives considered

- **Kubernetes (EKS / GKE)** — отвергнут: overhead для MVP, usecase не оправдан
- **ECS Fargate** — отвергнут: vendor lock, AWS-only, дороже VPS
- **Bare systemd без Docker** — отвергнут: теряем dev/prod parity (compose работает одинаково везде)
- **Serverless (Lambda + RDS)** — отвергнут: Laravel + Filament плохо ложатся, cold start боль

## Refs

- `docker-compose.prod.yml` — финальная конфигурация
- `docker/php/Dockerfile` target=prod
- `docker/nginx/prod.conf` — security headers + rate limits
- ADR-001 (modular monolith — совместимо с single-server)
- ADR-015 (JWT storage — Phase 2 после launch)
