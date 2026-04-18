<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Specification\SlotIsAvailable;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

it('is satisfied by a free slot', function (): void {
    $slot = TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );

    $spec = new SlotIsAvailable;

    expect($spec->isSatisfiedBy($slot))->toBeTrue();
});

it('is not satisfied by a booked slot', function (): void {
    $slot = TimeSlot::create(
        SlotId::generate(),
        ServiceId::generate(),
        new DateTimeImmutable('+2 days 10:00'),
        new DateTimeImmutable('+2 days 11:00'),
    );
    $slot->reserve(BookingId::generate());

    $spec = new SlotIsAvailable;

    expect($spec->isSatisfiedBy($slot))->toBeFalse();
    expect($spec->failureReason())->toContain('booked');
});

it('is not satisfied by non-TimeSlot candidate', function (): void {
    $spec = new SlotIsAvailable;

    expect($spec->isSatisfiedBy('not a slot'))->toBeFalse();
    expect($spec->failureReason())->toContain('TimeSlot');
});
