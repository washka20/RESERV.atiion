<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Specification\WithinBookingWindow;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Domain\ValueObject\Quantity;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeTimeSlotBookingAt(DateTimeImmutable $start): Booking
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

it('rejects booking in the past', function (): void {
    $spec = new WithinBookingWindow(minAdvanceMinutes: 60, maxAdvanceDays: 30);
    $booking = makeTimeSlotBookingAt(new DateTimeImmutable('-1 hour'));

    expect($spec->isSatisfiedBy($booking))->toBeFalse();
    expect($spec->failureReason())->toContain('too close');
});

it('rejects booking too close to start', function (): void {
    $spec = new WithinBookingWindow(minAdvanceMinutes: 120, maxAdvanceDays: 30);
    $booking = makeTimeSlotBookingAt(new DateTimeImmutable('+30 minutes'));

    expect($spec->isSatisfiedBy($booking))->toBeFalse();
});

it('rejects booking too far in future', function (): void {
    $spec = new WithinBookingWindow(minAdvanceMinutes: 60, maxAdvanceDays: 7);
    $booking = makeTimeSlotBookingAt(new DateTimeImmutable('+30 days'));

    expect($spec->isSatisfiedBy($booking))->toBeFalse();
    expect($spec->failureReason())->toContain('too far');
});

it('accepts booking within window', function (): void {
    $spec = new WithinBookingWindow(minAdvanceMinutes: 60, maxAdvanceDays: 30);
    $booking = makeTimeSlotBookingAt(new DateTimeImmutable('+2 days'));

    expect($spec->isSatisfiedBy($booking))->toBeTrue();
});

it('works with QUANTITY booking (uses dateRange->checkIn)', function (): void {
    $spec = new WithinBookingWindow(minAdvanceMinutes: 60, maxAdvanceDays: 30);
    $booking = Booking::createQuantityBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        dateRange: DateRange::fromStrings('+2 days', '+4 days'),
        quantity: new Quantity(1),
        totalPrice: Money::fromCents(100000, 'RUB'),
    );

    expect($spec->isSatisfiedBy($booking))->toBeTrue();
});

it('rejects non-Booking candidate', function (): void {
    $spec = new WithinBookingWindow(60, 30);

    expect($spec->isSatisfiedBy('not a booking'))->toBeFalse();
});
