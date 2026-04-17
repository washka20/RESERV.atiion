<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Event\BookingCancelled;
use App\Modules\Booking\Domain\Event\BookingCompleted;
use App\Modules\Booking\Domain\Event\BookingConfirmed;
use App\Modules\Booking\Domain\Event\BookingCreated;
use App\Modules\Booking\Domain\Exception\CancellationNotAllowedException;
use App\Modules\Booking\Domain\Exception\InvalidBookingStateTransitionException;
use App\Modules\Booking\Domain\Specification\CancellationPolicy;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Domain\ValueObject\Quantity;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeTimeSlotBooking(BookingStatus $initial = BookingStatus::PENDING): Booking
{
    $booking = Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('+2 days 10:00'),
            new DateTimeImmutable('+2 days 11:00'),
        ),
        totalPrice: Money::fromCents(100000, 'RUB'),
        notes: 'test',
    );

    if ($initial === BookingStatus::PENDING) {
        return $booking;
    }

    if ($initial === BookingStatus::CONFIRMED) {
        $booking->confirm();

        return $booking;
    }

    if ($initial === BookingStatus::COMPLETED) {
        $booking->confirm();
        $booking->complete();

        return $booking;
    }

    if ($initial === BookingStatus::CANCELLED) {
        $policy = mock(CancellationPolicy::class);
        $policy->shouldReceive('isSatisfiedBy')->andReturnTrue();
        $booking->cancel($policy);

        return $booking;
    }

    return $booking;
}

function containsEventOfType(array $events, string $class): bool
{
    foreach ($events as $event) {
        if ($event instanceof $class) {
            return true;
        }
    }

    return false;
}

it('creates a time slot booking via factory', function (): void {
    $booking = Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('+2 days 10:00'),
            new DateTimeImmutable('+2 days 11:00'),
        ),
        totalPrice: Money::fromCents(100000, 'RUB'),
        notes: 'test notes',
    );

    expect($booking->type)->toBe(BookingType::TIME_SLOT);
    expect($booking->status())->toBe(BookingStatus::PENDING);
    expect($booking->slotId)->not->toBeNull();
    expect($booking->quantity)->toBeNull();
    expect($booking->dateRange)->toBeNull();
    expect(containsEventOfType($booking->pullDomainEvents(), BookingCreated::class))->toBeTrue();
});

it('creates a quantity booking via factory', function (): void {
    $booking = Booking::createQuantityBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        dateRange: DateRange::fromStrings('+3 days', '+5 days'),
        quantity: new Quantity(2),
        totalPrice: Money::fromCents(400000, 'RUB'),
        notes: null,
    );

    expect($booking->type)->toBe(BookingType::QUANTITY);
    expect($booking->slotId)->toBeNull();
    expect($booking->timeRange)->toBeNull();
    expect($booking->dateRange)->not->toBeNull();
    expect($booking->quantity->value)->toBe(2);
});

it('confirms a pending booking and records BookingConfirmed event', function (): void {
    $booking = makeTimeSlotBooking();
    $booking->pullDomainEvents();

    $booking->confirm();

    expect($booking->status())->toBe(BookingStatus::CONFIRMED);
    expect(containsEventOfType($booking->pullDomainEvents(), BookingConfirmed::class))->toBeTrue();
});

it('cannot confirm non-pending booking', function (BookingStatus $initial): void {
    $booking = makeTimeSlotBooking($initial);
    $booking->confirm();
})->with([
    'confirmed' => BookingStatus::CONFIRMED,
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
])->throws(InvalidBookingStateTransitionException::class);

it('cancels booking when policy is satisfied', function (): void {
    $booking = makeTimeSlotBooking();
    $booking->pullDomainEvents();

    $policy = mock(CancellationPolicy::class);
    $policy->shouldReceive('isSatisfiedBy')->andReturnTrue();

    $booking->cancel($policy);

    expect($booking->status())->toBe(BookingStatus::CANCELLED);
    expect(containsEventOfType($booking->pullDomainEvents(), BookingCancelled::class))->toBeTrue();
});

it('rejects cancellation when policy fails', function (): void {
    $booking = makeTimeSlotBooking();
    $policy = mock(CancellationPolicy::class);
    $policy->shouldReceive('isSatisfiedBy')->andReturnFalse();
    $policy->shouldReceive('failureReason')->andReturn('too late to cancel');

    $booking->cancel($policy);
})->throws(CancellationNotAllowedException::class, 'too late to cancel');

it('completes confirmed booking and records BookingCompleted event', function (): void {
    $booking = makeTimeSlotBooking(BookingStatus::CONFIRMED);
    $booking->pullDomainEvents();

    $booking->complete();

    expect($booking->status())->toBe(BookingStatus::COMPLETED);
    expect(containsEventOfType($booking->pullDomainEvents(), BookingCompleted::class))->toBeTrue();
});

it('cannot complete non-confirmed booking', function (BookingStatus $initial): void {
    $booking = makeTimeSlotBooking($initial);
    $booking->complete();
})->with([
    'pending' => BookingStatus::PENDING,
    'cancelled' => BookingStatus::CANCELLED,
    'completed' => BookingStatus::COMPLETED,
])->throws(InvalidBookingStateTransitionException::class);

it('reports isActive according to status', function (BookingStatus $status, bool $expected): void {
    $booking = makeTimeSlotBooking($status);
    expect($booking->isActive())->toBe($expected);
})->with([
    'pending active' => [BookingStatus::PENDING, true],
    'confirmed active' => [BookingStatus::CONFIRMED, true],
    'cancelled inactive' => [BookingStatus::CANCELLED, false],
    'completed inactive' => [BookingStatus::COMPLETED, false],
]);

it('reconstitutes without recording BookingCreated', function (): void {
    $booking = Booking::reconstitute(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        type: BookingType::TIME_SLOT,
        status: BookingStatus::CONFIRMED,
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('+2 days 10:00'),
            new DateTimeImmutable('+2 days 11:00'),
        ),
        dateRange: null,
        quantity: null,
        totalPrice: Money::fromCents(100000, 'RUB'),
        notes: null,
        createdAt: new DateTimeImmutable('yesterday'),
        updatedAt: new DateTimeImmutable('now'),
    );

    expect($booking->status())->toBe(BookingStatus::CONFIRMED);
    expect($booking->pullDomainEvents())->toBe([]);
});
