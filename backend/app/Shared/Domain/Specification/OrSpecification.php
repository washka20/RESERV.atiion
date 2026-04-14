<?php

declare(strict_types=1);

namespace App\Shared\Domain\Specification;

/**
 * Дизъюнкция двух спецификаций. Удовлетворена, если хотя бы одна часть удовлетворена.
 */
final class OrSpecification extends Specification
{
    public function __construct(
        private readonly Specification $left,
        private readonly Specification $right,
    ) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        if ($this->left->isSatisfiedBy($candidate)) {
            return true;
        }

        if ($this->right->isSatisfiedBy($candidate)) {
            return true;
        }

        $this->recordFailure(sprintf(
            '%s; %s',
            (string) $this->left->failureReason(),
            (string) $this->right->failureReason()
        ));

        return false;
    }
}
