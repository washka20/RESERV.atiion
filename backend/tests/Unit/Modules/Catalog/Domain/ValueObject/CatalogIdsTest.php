<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Domain\ValueObject;

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Shared\Domain\AggregateId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CatalogIdsTest extends TestCase
{
    /**
     * @return iterable<string, array{0: class-string<AggregateId>}>
     */
    public static function idClasses(): iterable
    {
        yield 'ServiceId' => [ServiceId::class];
        yield 'CategoryId' => [CategoryId::class];
        yield 'SubcategoryId' => [SubcategoryId::class];
    }

    /**
     * @param  class-string<AggregateId>  $idClass
     */
    #[DataProvider('idClasses')]
    public function test_generate_creates_valid_uuid_id(string $idClass): void
    {
        $id = $idClass::generate();
        self::assertInstanceOf($idClass, $id);
        self::assertInstanceOf(AggregateId::class, $id);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $id->toString()
        );
    }

    /**
     * @param  class-string<AggregateId>  $idClass
     */
    #[DataProvider('idClasses')]
    public function test_from_string_accepts_valid_uuid(string $idClass): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = new $idClass($uuid);
        self::assertSame($uuid, $id->toString());
        self::assertSame($uuid, (string) $id);
    }

    /**
     * @param  class-string<AggregateId>  $idClass
     */
    #[DataProvider('idClasses')]
    public function test_rejects_invalid_uuid(string $idClass): void
    {
        $this->expectException(InvalidArgumentException::class);
        new $idClass('not-a-uuid');
    }

    /**
     * @param  class-string<AggregateId>  $idClass
     */
    #[DataProvider('idClasses')]
    public function test_equals_same_type_same_value(string $idClass): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $a = new $idClass($uuid);
        $b = new $idClass($uuid);
        self::assertTrue($a->equals($b));
    }

    public function test_equals_false_across_different_id_types(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        self::assertFalse((new ServiceId($uuid))->equals(new CategoryId($uuid)));
        self::assertFalse((new CategoryId($uuid))->equals(new SubcategoryId($uuid)));
    }
}
