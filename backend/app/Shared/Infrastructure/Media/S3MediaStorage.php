<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Media;

use App\Shared\Application\Media\MediaStorageInterface;
use App\Shared\Application\Media\MediaValidationException;
use App\Shared\Application\Media\UploadedFileInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Реализация MediaStorageInterface поверх Laravel Storage::disk('s3').
 *
 * Путь генерируется: {folder}/{uuid}.{extension}. Visibility=private —
 * единственный способ получить файл извне это signedUrl() с TTL.
 *
 * Валидация (mime/size/extension) — fail-fast в store(), до физической
 * записи. Config-driven через backend/config/media.php.
 */
final class S3MediaStorage implements MediaStorageInterface
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function store(UploadedFileInterface $file, string $folder): string
    {
        $this->validate($file);

        $ext = $file->clientExtension();
        $filename = Str::uuid()->toString().'.'.$ext;
        $folder = trim($folder, '/');
        $relativePath = $folder === '' ? $filename : "{$folder}/{$filename}";

        $this->disk()->putFileAs(
            $folder === '' ? '/' : $folder,
            new File($file->getRealPath()),
            $filename,
            [
                'visibility' => 'private',
                'ContentType' => $file->clientMimeType(),
            ]
        );

        return $relativePath;
    }

    public function delete(string $path): void
    {
        $this->disk()->delete($path);
    }

    public function signedUrl(string $path, ?int $ttlMinutes = null): string
    {
        $disk = $this->disk();

        if (! $disk instanceof FilesystemAdapter) {
            throw new RuntimeException('Disk does not support temporary URLs');
        }

        $ttl = $ttlMinutes ?? (int) $this->config->get('media.signed_url.ttl_minutes', 60);

        return $disk->temporaryUrl($path, now()->addMinutes($ttl));
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    private function disk(): Filesystem
    {
        $diskName = (string) $this->config->get('media.disk', 's3');

        return Storage::disk($diskName);
    }

    private function validate(UploadedFileInterface $file): void
    {
        $maxBytes = ((int) $this->config->get('media.max_size_kb', 10240)) * 1024;
        if ($file->sizeBytes() > $maxBytes) {
            throw MediaValidationException::size($file->sizeBytes(), $maxBytes);
        }

        $mimes = (array) $this->config->get('media.allowed_mimes', []);
        if (! in_array($file->clientMimeType(), $mimes, true)) {
            throw MediaValidationException::mime($file->clientMimeType());
        }

        $exts = (array) $this->config->get('media.allowed_extensions', []);
        $ext = $file->clientExtension();
        if (! in_array($ext, $exts, true)) {
            throw MediaValidationException::extension($ext);
        }
    }
}
