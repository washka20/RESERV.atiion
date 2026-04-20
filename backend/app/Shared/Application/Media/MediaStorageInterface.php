<?php

declare(strict_types=1);

namespace App\Shared\Application\Media;

/**
 * Абстракция media-хранилища (S3/MinIO в production, fake в тестах).
 *
 * Работает со строковыми path'ами — каждый модуль-потребитель (Catalog,
 * Identity для avatars позже) сам оборачивает в свой Domain VO если нужен
 * инвариант. См. ADR-017.
 *
 * Implementation detail: `signedUrl` возвращает temporary URL с TTL;
 * безопасно ротировать через `config('media.signed_url.ttl_minutes')`.
 */
interface MediaStorageInterface
{
    /**
     * Сохраняет файл в указанную папку, возвращает relative path.
     *
     * Имя файла — UUID.{extension}, чтобы избежать коллизий.
     *
     * @throws MediaValidationException при mime/size/extension нарушении
     * @throws \RuntimeException при сбое хранилища
     */
    public function store(UploadedFileInterface $file, string $folder): string;

    /**
     * Удаляет файл по path. Идемпотентно — нет файла, ок.
     */
    public function delete(string $path): void;

    /**
     * Возвращает signed URL с ограниченным TTL.
     *
     * TTL минуты — default из config('media.signed_url.ttl_minutes').
     */
    public function signedUrl(string $path, ?int $ttlMinutes = null): string;

    public function exists(string $path): bool;
}
