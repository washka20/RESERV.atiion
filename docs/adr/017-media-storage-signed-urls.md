# ADR 017: Media storage — private bucket + signed URLs + прагматичный scope

**Status:** Accepted
**Date:** 2026-04-20
**Supersedes:** parts of Plan 9 original spec (скипнули Shared ImagePath VO, cached-decorator до профилирования)

## Context

Plan 9 требует фото услуг в S3/MinIO. Два ключевых вопроса:

1. **Приватность:** надо ли делать bucket publicly readable или private + signed URLs?
2. **Scope of Shared kernel:** выносить ли `ImagePath` VO и cached-decorator в Shared — или оставить попроще?

## Decision

### 1. Bucket = private, URLs = signed с TTL

Bucket `reservatiion` — **private** (anonymous policy `none`). Все URL — temporary signed URLs с TTL:

- Default TTL: **60 минут** (`MEDIA_SIGNED_URL_TTL_MIN=60`)
- Generated on-demand в Query handlers через `MediaStorageInterface.signedUrl(path)`
- Возвращаются как обычные HTTP(S) URLs в API response

**Почему private:**
- Фото могут содержать private content клиентов/партнёров
- Утечка signed URL ограничена TTL (60 min по default)
- Нет публичного индекса (no `Google images` scraping)

### 2. Shared kernel минимальный

Скипнули из оригинального плана (по ADR-016 pragmatic scope):

- **Shared `ImagePath` VO** — оставили существующий в `Catalog\Domain\ValueObject`. Identity пока avatar'ы не грузит. Когда (если) понадобится второй модуль для image — тогда и вынесем.
- **`CachedSignedUrlMediaStorage` decorator** — отложен. Кэш нужен только при реальном RPS. Профилирование (Plan 11 k6) покажет. Добавить декоратор — 30 минут работы.

В Shared сейчас:
- `App\Shared\Application\Media\MediaStorageInterface` — контракт (store/delete/signedUrl/exists)
- `App\Shared\Application\Media\UploadedFileInterface` — абстракция над Laravel UploadedFile
- `App\Shared\Application\Media\MediaValidationException` — mime/size/extension errors
- `App\Shared\Infrastructure\Media\S3MediaStorage` — реализация поверх `Storage::disk('s3')`
- `App\Shared\Infrastructure\Media\LaravelUploadedFile` — адаптер
- `App\Shared\Infrastructure\Media\MediaServiceProvider` — bind interface → S3MediaStorage

### 3. Auto-register через providers.php

`MediaServiceProvider` добавлен в `bootstrap/providers.php`. Не через auto-discovery (в отличие от `Modules\*\Provider`) т.к. это Shared, не module.

### 4. Validation fail-fast

Валидация (mime / size / extension) делается в `S3MediaStorage::store()` **до** физической записи в bucket. Config-driven через `backend/config/media.php`:
- `max_size_kb` = 10240 (10 MB)
- `allowed_mimes` = jpeg/png/webp/gif
- `allowed_extensions` = jpg/jpeg/png/webp/gif

## Consequences

**Плюсы:**
- Privacy-by-default — no accidental public exposure
- Единый canonical способ выдачи URL — совместимо с CDN через CloudFront Origin Access (в Plan 11)
- Command handlers остаются тестируемыми через mock `MediaStorageInterface`
- Query handlers конвертируют stored paths в signed URLs — зеркалирует Filament UX

**Минусы:**
- Overhead генерации signed URL на каждом запросе каталога — **не митигирован пока** (см. "scope" выше)
- Если каталог крупный (>100 services per page) — задержка от AWS signing пропорциональна. Митигация: lazy generate на клиенте (IntersectionObserver), или decorator cache при profiling
- TTL 60 min = если URL попал в логи/cache, 1 час окно утечки

## Alternatives considered

- **Public bucket + CloudFront** — отвергнут: privacy concern + admin-only uploads не нуждаются в CDN edge на MVP
- **Prozy через Laravel** — отвергнут: нагрузка на PHP-FPM под RPS, теряется S3 streaming
- **Shared ImagePath VO сразу** — отвергнут: YAGNI, один модуль использует (ADR-016)
- **Cached signed URLs на старте** — отвергнут: преждевременная оптимизация без профилирования (ADR-016)

## Refs

- `backend/config/media.php` — конфиг
- `backend/app/Shared/Application/Media/` + `Shared/Infrastructure/Media/` — код
- `backend/tests/Feature/Shared/Infrastructure/Media/S3MediaStorageTest.php` — 11 тестов
- `backend/tests/Feature/Api/Catalog/SignedUrlInResponseTest.php` — integration
- ADR-016 — pragmatic DDD scope (обосновывает скип Shared VO и decorator)
