<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Domain\ValueObject;

use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ImagePathTest extends TestCase
{
    public function test_from_string_accepts_valid_relative_path(): void
    {
        $p = ImagePath::fromString('services/uuid/image.jpg');
        self::assertSame('services/uuid/image.jpg', $p->value());
    }

    public function test_rejects_path_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ImagePath::fromString('services/../etc/passwd');
    }

    public function test_rejects_empty_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ImagePath::fromString('');
    }

    public function test_rejects_leading_slash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ImagePath::fromString('/services/image.jpg');
    }

    public function test_equals_by_value(): void
    {
        $a = ImagePath::fromString('services/a.jpg');
        $b = ImagePath::fromString('services/a.jpg');
        $c = ImagePath::fromString('services/b.jpg');
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
