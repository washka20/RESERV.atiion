<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingType;

it('creates from string value', function (): void {
    expect(BookingType::from('time_slot'))->toBe(BookingType::TIME_SLOT);
    expect(BookingType::from('quantity'))->toBe(BookingType::QUANTITY);
});
