<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\Quantity;

it('creates with positive value', function (): void {
    $qty = new Quantity(5);

    expect($qty->value)->toBe(5);
});

it('rejects zero', function (): void {
    new Quantity(0);
})->throws(InvalidArgumentException::class, 'Quantity must be positive');

it('rejects negative value', function (): void {
    new Quantity(-3);
})->throws(InvalidArgumentException::class, 'Quantity must be positive');
