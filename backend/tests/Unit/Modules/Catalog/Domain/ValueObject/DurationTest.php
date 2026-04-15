<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Domain\ValueObject;

use App\Modules\Catalog\Domain\ValueObject\Duration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DurationTest extends TestCase
{
    public function test_of_minutes_creates_duration(): void
    {
        $d = Duration::ofMinutes(30);
        self::assertSame(30, $d->minutes());
    }

    public function test_rejects_zero_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Duration::ofMinutes(0);
    }

    public function test_rejects_negative_minutes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Duration::ofMinutes(-10);
    }

    public function test_to_hours_returns_float(): void
    {
        $d = Duration::ofMinutes(90);
        self::assertSame(1.5, $d->toHours());
    }

    public function test_add_sums_minutes(): void
    {
        $a = Duration::ofMinutes(30);
        $b = Duration::ofMinutes(45);
        self::assertSame(75, $a->add($b)->minutes());
    }

    public function test_equals_by_value(): void
    {
        self::assertTrue(Duration::ofMinutes(60)->equals(Duration::ofMinutes(60)));
        self::assertFalse(Duration::ofMinutes(60)->equals(Duration::ofMinutes(45)));
    }
}
