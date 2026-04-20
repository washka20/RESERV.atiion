# Security Hardening Checklist

Что реализовано в Plan 11 Phase 1 + что отложено в Phase 2.

## ✅ Реализовано

### Docker / Infrastructure

- [x] Non-root user в prod контейнере (`USER www-data` в Dockerfile prod)
- [x] БД/Redis/MinIO **не exposed** снаружи (docker-compose.prod.yml `ports: []`)
- [x] Resource limits (`deploy.resources.limits.memory/cpus`) — защита от OOM / fork-bomb
- [x] Logging rotation (`max-size: 10m`, `max-file: 3`) — защита от disk fill
- [x] HEALTHCHECK на каждом сервисе — balancer не шлёт на нерабочий pod
- [x] `restart: always` — автоматический recovery после крэша
- [x] Opcache + JIT в prod (php-prod.ini) — latency под нагрузкой

### HTTP / nginx

- [x] Security headers (HSTS, X-Content-Type-Options, X-Frame-Options: DENY, Referrer-Policy, Permissions-Policy, Cross-Origin-Opener-Policy)
- [x] Content Security Policy (default-src 'self' — restrictive, подтягиваем допущения по мере надобности)
- [x] `server_tokens off` — скрытие версии nginx
- [x] Rate limiting zones (api_general 30r/s, api_auth 5r/s, admin_general 10r/s)
- [x] `X-Robots-Tag: noindex` на `/admin` — не индексируется поисковиками
- [x] Gzip для текстовых content-type'ов
- [x] Deny для dotfiles (`/\.`)

### Application (Laravel)

- [x] Rate limiting на `/auth/register` (3/min), `/auth/login` (5/min), `/auth/refresh` (20/min)
- [x] Rate limiting на `POST /bookings` + `/cancel` (10/min)
- [x] Throttle на `PUT /auth/me` (10/min)
- [x] JWT required для всех non-public endpoints (middleware)
- [x] FormRequest validation (не raw input)
- [x] Eloquent prepared statements (SQL injection impossible by default)
- [x] CSRF для `/admin/*` (Filament session auth)
- [x] Password hashing через `Hash::make` (bcrypt)

### Env / Secrets

- [x] `.env` в `.gitignore` — никогда не коммитится
- [x] APP_KEY generated per-env, не разделяется между средами
- [x] JWT_SECRET отдельный, длина ≥ 32 char (в `.env.example` задокументирован min)
- [x] AWS credentials через env var — не hardcoded

## ⏳ Отложено в Phase 2

### Infrastructure

- [ ] SSL / Let's Encrypt auto-renewal (cron + certbot)
- [ ] Docker secrets (BuildKit `--secret`) вместо env vars для прод passwords
- [ ] Immutable infrastructure (packer/terraform) — сейчас hand-managed VPS
- [ ] Firewall rules на хосте (ufw allow 80/443/22 only)
- [ ] fail2ban для SSH brute-force

### Application

- [ ] JWT → httpOnly cookie (ADR-015 Phase 2) — breaking change
- [ ] 2FA для admin users
- [ ] Session fingerprinting (IP + User-Agent validation)
- [ ] Password complexity policy (сейчас только min-length=8 в RegisterRequest)
- [ ] Account lockout after N failed logins (сейчас throttle 5/min)
- [ ] Email verification required до первого booking (сейчас optional)

### Monitoring / Incident Response

- [ ] Sentry integration для prod exceptions
- [ ] Laravel Telescope (dev-only) для request introspection
- [ ] Prometheus `/metrics` endpoint для scraping
- [ ] Audit log для admin actions (Filament changes)
- [ ] Auth events log (login success/failure, password change) — отдельная таблица

### Dependencies

- [ ] Weekly `composer audit` (уже есть в CI, но на push only — нужен schedule)
- [ ] Weekly `npm audit` (same)
- [ ] `trivy` scan prod images (уже есть в security workflow)
- [ ] Dependabot для auto PR (composer, npm, docker, github-actions)

### Backup / Disaster Recovery

- [ ] `pg_dump` nightly cron → S3 (retention 30 days)
- [ ] MinIO bucket replication (cross-region)
- [ ] Restore runbook tested в dev

### Compliance / Legal

- [ ] Cookie consent banner (GDPR)
- [ ] Privacy policy / Terms of Service (нет пока)
- [ ] Data retention policy (сколько хранить booking history)
- [ ] PII audit (что персональное хранится, где, TTL)

## Как пользоваться

1. При выпуске в prod пробежаться по ✅ — убедиться что ни один чекбокс не пропал.
2. Смотреть ⏳ блок — расставить приоритет под реальный risk surface.
3. При добавлении новой фичи — проверить что не открывает **surface** из отложенного списка.

## References

- [ADR-018 Deployment strategy](../adr/018-deployment-strategy.md)
- [ADR-015 JWT storage phases](../adr/015-jwt-storage-strategy.md)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
