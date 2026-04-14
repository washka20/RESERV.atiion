<?php

declare(strict_types=1);

namespace App\Shared\Domain;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Stringable;

/**
 * Base value object for aggregate identifiers.
 *
 * Extend this class for each aggregate root to create a strongly-typed identifier.
 * All IDs are UUID-backed and validated on construction.
 */
abstract class AggregateId implements Stringable
{
    public function __construct(private readonly string $value)
    {
        if (! Uuid::isValid($value)) {
            throw new InvalidArgumentException(
                sprintf('%s expects a valid UUID, got "%s"', static::class, $value)
            );
        }
    }

    /**
     * Creates a new random UUID v4 identifier.
     */
    public static function generate(): static
    {
        return new static(Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Compares by value and concrete type — two IDs of different types are never equal,
     * even if they hold the same UUID string.
     */
    public function equals(AggregateId $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
