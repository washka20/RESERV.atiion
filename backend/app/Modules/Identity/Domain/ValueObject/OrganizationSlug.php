<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Slug организации — используется в URL /o/{slug}/*.
 *
 * Формат: 3-64 символа; только [a-z0-9-]; начинается и заканчивается на [a-z0-9]
 * (не на дефис); без `--` подряд. Совпадает с PG CHECK constraint в миграции.
 *
 * Slug генерируется автоматически из имени при создании Organization
 * (см. SlugGenerator service), owner может изменить один раз через settings.
 */
final readonly class OrganizationSlug implements Stringable
{
    private const MIN_LENGTH = 3;

    private const MAX_LENGTH = 64;

    private const PATTERN = '/^[a-z0-9][a-z0-9-]{1,62}[a-z0-9]$/';

    public function __construct(public string $value)
    {
        $length = strlen($value);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'OrganizationSlug: длина должна быть %d-%d символов, получено %d',
                self::MIN_LENGTH,
                self::MAX_LENGTH,
                $length,
            ));
        }

        if (! preg_match(self::PATTERN, $value)) {
            throw new InvalidArgumentException(sprintf(
                'OrganizationSlug: "%s" должен содержать только [a-z0-9-], не начинаться/заканчиваться на дефис',
                $value,
            ));
        }

        if (str_contains($value, '--')) {
            throw new InvalidArgumentException(sprintf(
                'OrganizationSlug: "%s" не должен содержать двойные дефисы',
                $value,
            ));
        }
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
