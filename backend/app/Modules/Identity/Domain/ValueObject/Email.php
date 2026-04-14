<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use App\Modules\Identity\Domain\Exception\InvalidEmailException;

/**
 * Email value object. Normalizes to lowercase on construction.
 */
final readonly class Email
{
    private const PATTERN = '/^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$/i';

    private string $canonical;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));
        if ($normalized === '' || ! preg_match(self::PATTERN, $normalized)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
        $this->canonical = $normalized;
    }

    public function value(): string
    {
        return $this->canonical;
    }

    public function equals(self $other): bool
    {
        return $this->canonical === $other->canonical;
    }
}
