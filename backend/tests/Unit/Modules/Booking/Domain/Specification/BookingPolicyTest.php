<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Specification\BookingPolicy;
use App\Modules\Booking\Domain\Specification\UserNotExceedsLimit;
use App\Modules\Booking\Domain\Specification\WithinBookingWindow;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeBookingForPolicy(DateTimeImmutable $start): Booking
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

it('is satisfied when within window and user under limit', function (): void {
    $policy = new BookingPolicy(
        new WithinBookingWindow(minAdvanceMinutes: 60, maxAdvanceDays: 30),
        new UserNotExceedsLimit,
    );
    $booking = makeBookingForPolicy(new DateTimeImmutable('+2 days'));

    expect($policy->isSatisfiedByWithContext($booking, userActiveBookings: 2, limit: 5))->toBeTrue();
});

it('fails when outside booking window', function (): void {
    $policy = new BookingPolicy(
        new WithinBookingWindow(minAdvanceMinutes: 60, maxAdvanceDays: 7),
        new UserNotExceedsLimit,
    );
    $booking = makeBookingForPolicy(new DateTimeImmutable('+30 minutes'));

    expect($policy->isSatisfiedByWithContext($booking, 0, 5))->toBeFalse();
    expect($policy->failureReason())->toContain('too close');
});

it('fails when user reached limit', function (): void {
    $policy = new BookingPolicy(
        new WithinBookingWindow(60, 30),
        new UserNotExceedsLimit,
    );
    $booking = makeBookingForPolicy(new DateTimeImmutable('+2 days'));

    expect($policy->isSatisfiedByWithContext($booking, userActiveBookings: 5, limit: 5))->toBeFalse();
    expect($policy->failureReason())->toContain('limit');
});
