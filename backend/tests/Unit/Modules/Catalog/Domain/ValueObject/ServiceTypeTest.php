<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Domain\ValueObject;

use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use PHPUnit\Framework\TestCase;

final class ServiceTypeTest extends TestCase
{
    public function test_time_slot_value(): void
    {
        self::assertSame('time_slot', ServiceType::TIME_SLOT->value);
    }

    public function test_quantity_value(): void
    {
        self::assertSame('quantity', ServiceType::QUANTITY->value);
    }

    public function test_from_returns_enum(): void
    {
        self::assertSame(ServiceType::TIME_SLOT, ServiceType::from('time_slot'));
        self::assertSame(ServiceType::QUANTITY, ServiceType::from('quantity'));
    }

    public function test_requires_duration(): void
    {
        self::assertTrue(ServiceType::TIME_SLOT->requiresDuration());
        self::assertFalse(ServiceType::QUANTITY->requiresDuration());
    }

    public function test_requires_total_quantity(): void
    {
        self::assertFalse(ServiceType::TIME_SLOT->requiresTotalQuantity());
        self::assertTrue(ServiceType::QUANTITY->requiresTotalQuantity());
    }
}
