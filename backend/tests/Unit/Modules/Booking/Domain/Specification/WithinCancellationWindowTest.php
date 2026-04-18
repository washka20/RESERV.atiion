<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Specification\WithinCancellationWindow;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeCancellableBooking(DateTimeImmutable $start): Booking
{
    return Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        slotId: SlotId::generate(),
        timeRange: new TimeRange($start, $start->modify('+1 hour')),
        totalPrice: Money::fromCents(100000, 'RUB'),
    );
}

it('allows cancel when far enough from start', function (): void {
    $spec = new WithinCancellationWindow(minHoursBefore: 24);
    $booking = makeCancellableBooking(new DateTimeImmutable('+3 days'));

    expect($spec->isSatisfiedBy($booking))->toBeTrue();
});

it('rejects cancel when too close to start', function (): void {
    $spec = new WithinCancellationWindow(minHoursBefore: 24);
    $booking = makeCancellableBooking(new DateTimeImmutable('+2 hours'));

    expect($spec->isSatisfiedBy($booking))->toBeFalse();
    expect($spec->failureReason())->toContain('too late');
});
