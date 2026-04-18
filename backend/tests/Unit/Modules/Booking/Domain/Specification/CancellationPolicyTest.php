<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Specification\BookingNotAlreadyCompleted;
use App\Modules\Booking\Domain\Specification\CancellationPolicy;
use App\Modules\Booking\Domain\Specification\WithinCancellationWindow;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeBookingAt(BookingStatus $status, DateTimeImmutable $start): Booking
{
    return Booking::reconstitute(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        type: BookingType::TIME_SLOT,
        status: $status,
        slotId: SlotId::generate(),
        timeRange: new TimeRange($start, $start->modify('+1 hour')),
        dateRange: null,
        quantity: null,
        totalPrice: Money::fromCents(100000, 'RUB'),
        notes: null,
        createdAt: new DateTimeImmutable('-1 day'),
        updatedAt: new DateTimeImmutable,
    );
}

it('is satisfied when booking not completed and within window', function (): void {
    $policy = new CancellationPolicy(
        new BookingNotAlreadyCompleted,
        new WithinCancellationWindow(minHoursBefore: 24),
    );
    $booking = makeBookingAt(BookingStatus::CONFIRMED, new DateTimeImmutable('+3 days'));

    expect($policy->isSatisfiedBy($booking))->toBeTrue();
});

it('fails when booking is already completed', function (): void {
    $policy = new CancellationPolicy(
        new BookingNotAlreadyCompleted,
        new WithinCancellationWindow(minHoursBefore: 24),
    );
    $booking = makeBookingAt(BookingStatus::COMPLETED, new DateTimeImmutable('+3 days'));

    expect($policy->isSatisfiedBy($booking))->toBeFalse();
    expect($policy->failureReason())->toContain('completed');
});

it('fails when booking is too close to start', function (): void {
    $policy = new CancellationPolicy(
        new BookingNotAlreadyCompleted,
        new WithinCancellationWindow(minHoursBefore: 24),
    );
    $booking = makeBookingAt(BookingStatus::CONFIRMED, new DateTimeImmutable('+2 hours'));

    expect($policy->isSatisfiedBy($booking))->toBeFalse();
    expect($policy->failureReason())->toContain('too late');
});
