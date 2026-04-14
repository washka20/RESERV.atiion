<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

/**
 * Конъюнкция двух спецификаций. Удовлетворена, если обе части удовлетворены.
 */
final class AndSpecification extends Specification
{
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if (! $this->left->isSatisfiedBy($candidate)) {
            $this->recordFailure((string) $this->left->failureReason());

            return false;
        }

        if (! $this->right->isSatisfiedBy($candidate)) {
            $this->recordFailure((string) $this->right->failureReason());

            return false;
        }

        return true;
    }
}
