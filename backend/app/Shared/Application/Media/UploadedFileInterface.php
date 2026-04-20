<?php

declare(strict_types=1);

namespace App\Shared\Application\Media;

/**
 * Абстракция над Illuminate\Http\UploadedFile для чистоты Application-слоя.
 *
 * Application не должен знать про Laravel — конкретная реализация
 * LaravelUploadedFile живёт в Infrastructure.
 */
interface UploadedFileInterface
{
    public function clientMimeType(): string;

    public function clientExtension(): string;

    public function sizeBytes(): int;

    public function getRealPath(): string;

    public function getClientOriginalName(): string;
}
