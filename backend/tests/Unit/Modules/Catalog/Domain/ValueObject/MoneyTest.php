<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Domain\ValueObject;

use App\Modules\Catalog\Domain\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function test_from_cents_creates_money(): void
    {
        $m = Money::fromCents(10050, 'RUB');
        self::assertSame(10050, $m->amount());
        self::assertSame('RUB', $m->currency());
    }

    public function test_from_rubles_converts_to_cents(): void
    {
        $m = Money::fromRubles(100.50);
        self::assertSame(10050, $m->amount());
        self::assertSame('RUB', $m->currency());
    }

    public function test_rejects_negative_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(-1, 'RUB');
    }

    public function test_rejects_unknown_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100, 'JPY');
    }

    public function test_add_same_currency(): void
    {
        $a = Money::fromCents(100, 'RUB');
        $b = Money::fromCents(250, 'RUB');
        self::assertSame(350, $a->add($b)->amount());
    }

    public function test_add_rejects_mixed_currency(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromCents(100, 'RUB')->add(Money::fromCents(100, 'USD'));
    }

    public function test_equality_value_object(): void
    {
        self::assertTrue(
            Money::fromCents(100, 'RUB')->equals(Money::fromCents(100, 'RUB'))
        );
    }
}
