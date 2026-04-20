# Media Storage Pattern

> Реализация: [ADR-017](../adr/017-media-storage-signed-urls.md)

## Overview

Приватный S3/MinIO bucket, доступ через signed URLs с TTL. Абстракция `MediaStorageInterface` в Shared — модули не зависят от конкретного S3 SDK.

## Flow

### Upload

```
Filament UI → $request->file('images')
  → LaravelUploadedFile wrapper
    → AddServiceImageCommand(serviceId, file)
      → CommandBus.dispatch
        → AddServiceImageHandler
          → MediaStorageInterface::store(file, "services/{id}")
            → validate (mime/size/ext) → fail-fast
            → UUID filename → S3 putFileAs(visibility=private)
            → return "services/{id}/{uuid}.jpg"
          → service.addImage(ImagePath(path))
          → services.save(service)
          → dispatcher.dispatchAll(events)
```

### Read (caller → API)

```
GET /api/v1/services/{id}
  → ServiceController.show
    → QueryBus.ask(GetServiceQuery)
      → GetServiceHandler (DB::table + signedUrl)
        → for each stored path:
            → MediaStorage.signedUrl(path, ttl)
              → temporaryUrl → https://minio:.../X-Amz-Signature=...&X-Amz-Expires=3600
        → DTO(images: [url1, url2, ...])
    → ServiceResource.fromDTO
    → HTTP 200 { data: { images: [url1, url2] } }
```

### Delete

```
RemoveServiceImageCommand(serviceId, path)
  → RemoveServiceImageHandler
    → service.removeImage(ImagePath(path))
    → services.save(service)
    → dispatcher.dispatchAll(events)
    → MediaStorage.delete(path)    ← после save, чтобы при падении save файл остался
```

## Config (backend/config/media.php)

| Key | Default | Описание |
|---|---|---|
| `disk` | `s3` | Laravel filesystem disk |
| `max_size_kb` | 10240 | Макс размер (10 MB) |
| `allowed_mimes` | `image/{jpeg,png,webp,gif}` | MIME whitelist |
| `allowed_extensions` | `jpg,jpeg,png,webp,gif` | Ext whitelist |
| `signed_url.ttl_minutes` | 60 | TTL URL |

Env overrides: `MEDIA_DISK`, `MEDIA_MAX_SIZE_KB`, `MEDIA_SIGNED_URL_TTL_MIN`.

## Валидация

`S3MediaStorage::validate()` throws `MediaValidationException` fail-fast:
- mime в `allowed_mimes` — иначе `MediaValidationException::mime($mime)`
- size ≤ `max_size_kb * 1024` — иначе `::size($bytes, $maxBytes)`
- extension в `allowed_extensions` — иначе `::extension($ext)`

Controllers выше ловят `MediaValidationException` и возвращают 422 / Filament показывает error.

## Тестирование

Unit + feature тесты через `Storage::fake('s3')`:

```php
beforeEach(function (): void {
    Storage::fake('s3');
});

it('uploads file', function (): void {
    $file = new LaravelUploadedFile(UploadedFile::fake()->image('p.jpg'));
    $path = app(MediaStorageInterface::class)->store($file, 'services/x');

    Storage::disk('s3')->assertExists($path);
});
```

Интеграционные против реального MinIO — запускать через `MEDIA_DISK=s3_real` + docker-compose MinIO.

## Production чеклист

- AWS S3 real / Yandex Object Storage — `AWS_ENDPOINT=` empty, `AWS_USE_PATH_STYLE_ENDPOINT=false`
- IAM user с scope `s3:PutObject|DeleteObject|GetObject` на bucket
- CORS origins = list of prod domains
- Bucket policy — private (anonymous policy `none`)
- CDN: CloudFront с OAI (Origin Access Identity) для signed URLs в Plan 11

## Что НЕ сделано пока (отложено)

- **Cached signed URLs decorator** — добавим после profiling в Plan 11 k6
- **Shared `ImagePath` VO** — пока только Catalog. Extrахned когда Identity/Landing будут использовать
- **Image processing (thumbnails, resize)** — отдельный план, через `intervention/image`
- **Direct customer upload** — MVP только admin через Filament

См. [ADR-017](../adr/017-media-storage-signed-urls.md) для deep-dive.
