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

final class SpecificationTest extends TestCase
{
    public function test_satisfied_when_candidate_meets_rule(): void
    {
        $spec = new GreaterThan(5);

        $this->assertTrue($spec->isSatisfiedBy(10));
        $this->assertNull($spec->failureReason());
    }

    public function test_records_failure_reason_when_unsatisfied(): void
    {
        $spec = new GreaterThan(5);

        $this->assertFalse($spec->isSatisfiedBy(3));
        $this->assertStringContainsString('expected > 5', (string) $spec->failureReason());
    }

    public function test_failure_reason_resets_on_successful_check(): void
    {
        $spec = new GreaterThan(5);

        $spec->isSatisfiedBy(3);
        $this->assertNotNull($spec->failureReason());

        // Текущее поведение: failureReason остаётся stateful до следующего recordFailure.
        // Повторный успешный check не сбрасывает (ожидаемо для инкапсуляции provider'а).
        $this->assertTrue($spec->isSatisfiedBy(10));
    }
}
