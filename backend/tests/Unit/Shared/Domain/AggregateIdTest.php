<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain;

use App\Shared\Domain\AggregateId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SomeTestId extends AggregateId {}
final class OtherTestId extends AggregateId {}

final class AggregateIdTest extends TestCase
{
    public function test_constructs_from_valid_uuid(): void
    {
        $id = new SomeTestId('0191e8a4-4c1f-7e2a-9b3c-7f1a5d9e4b0d');
        $this->assertSame('0191e8a4-4c1f-7e2a-9b3c-7f1a5d9e4b0d', $id->toString());
    }

    public function test_rejects_invalid_uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new SomeTestId('not-a-uuid');
    }

    public function test_generate_produces_valid_uuid_v4(): void
    {
        $id = SomeTestId::generate();
        $this->assertInstanceOf(SomeTestId::class, $id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->toString()
        );
    }

    public function test_equals_compares_value_and_type(): void
    {
        $uuid = '0191e8a4-4c1f-7e2a-9b3c-7f1a5d9e4b0d';
        $this->assertTrue((new SomeTestId($uuid))->equals(new SomeTestId($uuid)));
        $this->assertFalse((new SomeTestId($uuid))->equals(new OtherTestId($uuid)));
    }

    public function test_cast_to_string(): void
    {
        $uuid = '0191e8a4-4c1f-7e2a-9b3c-7f1a5d9e4b0d';
        $this->assertSame($uuid, (string) new SomeTestId($uuid));
    }
}
