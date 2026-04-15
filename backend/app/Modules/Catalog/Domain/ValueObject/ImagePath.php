<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

use InvalidArgumentException;

/**
 * Относительный путь к изображению в S3/MinIO.
 *
 * Защищён от path traversal (`..`) и абсолютных путей (leading `/`).
 */
final readonly class ImagePath
{
    private function __construct(private string $path)
    {
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new InvalidArgumentException("Invalid image path: {$path}");
        }
    }

    public static function fromString(string $path): self
    {
        return new self($path);
    }

    public function value(): string
    {
        return $this->path;
    }

    public function equals(self $other): bool
    {
        return $this->path === $other->path;
    }
}
