<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain;

use App\Shared\Domain\Specification\Specification;
use PHPUnit\Framework\TestCase;

final class GreaterThan extends Specification
{
    public function __construct(private readonly int $threshold) {}

    public function isSatisfiedBy(mixed $candidate): bool
    {
        $ok = is_int($candidate) && $candidate > $this->threshold;
        if (! $ok) {
            $this->recordFailure("expected > {$this->threshold}");
        }

        return $ok;
    }
}

final class Even extends Specification
{
    public function isSatisfiedBy(mixed $candidate): bool
    {
        $ok = is_int($candidate) && $candidate % 2 === 0;
        if (! $ok) {
            $this->recordFailure('expected even');
        }

        return $ok;
    }
}

final class SpecificationTest extends TestCase
{
    public function test_and_requires_both(): void
    {
        $spec = (new GreaterThan(5))->and(new Even);

        $this->assertTrue($spec->isSatisfiedBy(6));
        $this->assertFalse($spec->isSatisfiedBy(4));
        $this->assertFalse($spec->isSatisfiedBy(7));
    }

    public function test_or_requires_either(): void
    {
        $spec = (new GreaterThan(10))->or(new Even);

        $this->assertTrue($spec->isSatisfiedBy(4));
        $this->assertTrue($spec->isSatisfiedBy(11));
        $this->assertFalse($spec->isSatisfiedBy(3));
    }

    public function test_not_inverts(): void
    {
        $spec = (new Even)->not();

        $this->assertTrue($spec->isSatisfiedBy(3));
        $this->assertFalse($spec->isSatisfiedBy(4));
    }

    public function test_failure_reason_exposes_first_unsatisfied(): void
    {
        $spec = (new GreaterThan(5))->and(new Even);

        $spec->isSatisfiedBy(3);
        $this->assertStringContainsString('expected > 5', (string) $spec->failureReason());
    }

    public function test_failure_reason_is_null_when_satisfied(): void
    {
        $spec = new GreaterThan(5);
        $spec->isSatisfiedBy(10);

        $this->assertNull($spec->failureReason());
    }

    public function test_composition_is_associative(): void
    {
        $spec = (new GreaterThan(0))->and(new GreaterThan(5))->and(new Even);

        $this->assertTrue($spec->isSatisfiedBy(8));
        $this->assertFalse($spec->isSatisfiedBy(-2));
    }
}
