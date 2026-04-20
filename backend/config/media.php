<?php

declare(strict_types=1);

/**
 * Настройки media-модуля: хранилище + лимиты + signed URL.
 *
 * См. ADR-017. Cached-decorator для signed URL не включён по умолчанию —
 * добавим когда profiling покажет реальный RPS (ADR-016 "не оптимизируй
 * преждевременно").
 */
return [
    'disk' => env('MEDIA_DISK', 's3'),
    'max_size_kb' => (int) env('MEDIA_MAX_SIZE_KB', 10240),
    'allowed_mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    'signed_url' => [
        'ttl_minutes' => (int) env('MEDIA_SIGNED_URL_TTL_MIN', 60),
    ],
];
