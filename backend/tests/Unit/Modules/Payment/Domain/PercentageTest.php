<?php

declare(strict_types=1);

use App\Modules\Payment\Domain\ValueObject\Percentage;

it('percentage accepts 0 to 100', function () {
    expect(Percentage::fromInt(0)->value())->toBe(0);
    expect(Percentage::fromInt(10)->value())->toBe(10);
    expect(Percentage::fromInt(100)->value())->toBe(100);
});

it('percentage rejects negative', fn () => Percentage::fromInt(-1))
    ->throws(InvalidArgumentException::class);

it('percentage rejects > 100', fn () => Percentage::fromInt(101))
    ->throws(InvalidArgumentException::class);

it('two percentages with same value are equal', function () {
    expect(Percentage::fromInt(10)->equals(Percentage::fromInt(10)))->toBeTrue();
    expect(Percentage::fromInt(10)->equals(Percentage::fromInt(11)))->toBeFalse();
});
