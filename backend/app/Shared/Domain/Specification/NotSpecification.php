<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

/**
 * Инверсия спецификации. Удовлетворена, если внутренняя спецификация не удовлетворена.
 */
final class NotSpecification extends Specification
{
    public function __construct(private readonly Specification $inner) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        $ok = ! $this->inner->isSatisfiedBy($candidate);
        if (! $ok) {
            $this->recordFailure('negation failed: inner spec satisfied');
        }

        return $ok;
    }
}
