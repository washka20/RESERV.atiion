# S3 / MinIO Setup

## Dev — MinIO (Docker Compose)

Уже поднято в `docker-compose.yml`. Console на `http://localhost:9001` (логин `minioadmin` / `minioadmin`).

Init-скрипт (`docker/minio/init.sh`) создаёт bucket `reservatiion` при `make up`.

Проверка:
```bash
docker compose exec minio mc alias set local http://minio:9000 minioadmin minioadmin
docker compose exec minio mc ls local/
# → reservatiion/
```

## Env (backend/.env)

```dotenv
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=reservatiion
AWS_ENDPOINT=http://minio:9000
AWS_URL=http://localhost:9000/reservatiion
AWS_USE_PATH_STYLE_ENDPOINT=true

MEDIA_DISK=s3
MEDIA_MAX_SIZE_KB=10240
MEDIA_SIGNED_URL_TTL_MIN=60
```

## Production — AWS S3

1. **IAM user** с access-key/secret, attached policy:

```json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Action": ["s3:GetObject", "s3:PutObject", "s3:DeleteObject"],
    "Resource": "arn:aws:s3:::reservatiion-prod/*"
  }, {
    "Effect": "Allow",
    "Action": ["s3:ListBucket"],
    "Resource": "arn:aws:s3:::reservatiion-prod"
  }]
}
```

2. **Bucket policy** — private (no public-read), CORS только с prod domains:

```json
{
  "CORSRules": [{
    "AllowedOrigins": ["https://reserv.example.com"],
    "AllowedMethods": ["GET", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }]
}
```

3. **Env prod**:

```dotenv
AWS_ACCESS_KEY_ID=<IAM>
AWS_SECRET_ACCESS_KEY=<IAM>
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=reservatiion-prod
AWS_ENDPOINT=          # empty — use AWS default
AWS_USE_PATH_STYLE_ENDPOINT=false
AWS_URL=               # empty — temporaryUrl() сгенерирует
```

## Production — Yandex Object Storage

Совместимый с S3. Endpoint: `https://storage.yandexcloud.net`. Регион `ru-central1`.

```dotenv
AWS_DEFAULT_REGION=ru-central1
AWS_BUCKET=reservatiion-prod
AWS_ENDPOINT=https://storage.yandexcloud.net
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Создать service account с ролью `storage.editor`, сгенерировать static access key.

## Smoke test

```bash
# dev
docker compose exec php php artisan tinker
>>> Storage::disk('s3')->put('test.txt', 'hello')
>>> Storage::disk('s3')->exists('test.txt')
=> true
>>> Storage::disk('s3')->delete('test.txt')
```

Через MinIO Console (`http://localhost:9001`) → bucket `reservatiion` → файл виден/пропал.

## CDN (Plan 11)

CloudFront с OAI (Origin Access Identity) перед S3 — signed URLs генерируются через `temporaryUrl()` но отдаются через edge-cache CloudFront. TTL кэша на edge ≤ TTL signed URL.
