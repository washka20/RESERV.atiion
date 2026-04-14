<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

use App\Modules\Identity\Domain\Service\PasswordHasherInterface;
use InvalidArgumentException;

/**
 * Hashed password value object. Never stores plaintext.
 */
final readonly class HashedPassword
{
    public function __construct(private string $hash)
    {
        if ($hash === '') {
            throw new InvalidArgumentException('HashedPassword cannot be empty');
        }
    }

    /**
     * Creates a HashedPassword from a plaintext string using the provided hasher.
     */
    public static function fromPlaintext(string $plain, PasswordHasherInterface $hasher): self
    {
        return new self($hasher->hash($plain));
    }

    public function value(): string
    {
        return $this->hash;
    }

    /**
     * Verifies plaintext against the stored hash.
     */
    public function matches(string $plain, PasswordHasherInterface $hasher): bool
    {
        return $hasher->check($plain, $this->hash);
    }
}
