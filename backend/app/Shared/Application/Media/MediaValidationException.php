<?php

declare(strict_types=1);

namespace App\Shared\Application\Media;

use RuntimeException;

/**
 * Исключение валидации загружаемого файла: mime/size/extension.
 *
 * Не DomainException т.к. это Application-layer concern (валидация
 * внешнего input'а), не бизнес-инвариант домена.
 */
final class MediaValidationException extends RuntimeException
{
    public static function mime(string $mime): self
    {
        return new self("Media mime '{$mime}' is not allowed");
    }

    public static function size(int $bytes, int $maxBytes): self
    {
        return new self("Media size {$bytes} exceeds max {$maxBytes}");
    }

    public static function extension(string $ext): self
    {
        return new self("Media extension '{$ext}' is not allowed");
    }
}
