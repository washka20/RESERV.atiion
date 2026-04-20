<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Media;

use App\Shared\Application\Media\UploadedFileInterface;
use Illuminate\Http\UploadedFile;

/**
 * Адаптер Illuminate\Http\UploadedFile → UploadedFileInterface.
 *
 * Контроллеры / Filament обёртывают $request->file() в этот адаптер
 * перед dispatch в CommandBus, чтобы handler не зависел от Laravel.
 */
final readonly class LaravelUploadedFile implements UploadedFileInterface
{
    public function __construct(private UploadedFile $file) {}

    public function clientMimeType(): string
    {
        return $this->file->getClientMimeType();
    }

    public function clientExtension(): string
    {
        return strtolower($this->file->getClientOriginalExtension());
    }

    public function sizeBytes(): int
    {
        return (int) $this->file->getSize();
    }

    public function getRealPath(): string
    {
        return (string) $this->file->getRealPath();
    }

    public function getClientOriginalName(): string
    {
        return $this->file->getClientOriginalName();
    }
}
