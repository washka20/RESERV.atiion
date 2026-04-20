# Platform BC

Cross-cutting infrastructure concerns: health, metrics, scheduling. Не бизнес-модуль, но живёт как модуль чтобы попасть в auto-discovery (ADR-016).

## Endpoints

- `GET /api/v1/health` — readiness probe, без auth. Возвращает статусы Postgres, Redis cache, S3 storage. 200 для healthy/degraded, 503 для unhealthy.

## Domain / Application

- `Domain\ValueObject\HealthStatus` — enum (healthy/degraded/unhealthy)
- `Application\HealthChecker` — probe всех dependencies

## Dependencies

- `Illuminate\Database\Connection` — БД
- `Illuminate\Contracts\Cache\Repository` — Redis cache
- `Illuminate\Contracts\Filesystem\Filesystem` — storage adapter (читается через alias `Storage::disk('s3')` в `config/filesystems.php`)
