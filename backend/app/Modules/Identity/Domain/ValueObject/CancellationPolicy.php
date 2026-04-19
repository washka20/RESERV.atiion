<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

/**
 * Политика отмены бронирования, задаваемая провайдером.
 *
 * FLEXIBLE — можно отменить минимум за 1 час до начала
 * MODERATE — минимум за 24 часа
 * STRICT — минимум за 72 часа
 *
 * Значения используются Booking BC в WithinCancellationWindow specification.
 */
enum CancellationPolicy: string
{
    case FLEXIBLE = 'flexible';
    case MODERATE = 'moderate';
    case STRICT = 'strict';

    public function minHoursBefore(): int
    {
        return match ($this) {
            self::FLEXIBLE => 1,
            self::MODERATE => 24,
            self::STRICT => 72,
        };
    }
}
