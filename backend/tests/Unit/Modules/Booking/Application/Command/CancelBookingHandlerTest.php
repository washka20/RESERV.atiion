<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\CancelBooking\CancelBookingCommand;
use App\Modules\Booking\Application\Command\CancelBooking\CancelBookingHandler;
use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Exception\CancellationNotAllowedException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\Specification\CancellationPolicy;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Booking\Domain\ValueObject\Quantity;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    DB::shouldReceive('transaction')->andReturnUsing(fn (callable $cb) => $cb());
});

function makeTimeSlotBookingForCancel(UserId $userId, SlotId $slotId): Booking
{
    return Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: $userId,
        serviceId: ServiceId::generate(),
        slotId: $slotId,
        timeRange: new TimeRange(
            new DateTimeImmutable('+3 days 10:00'),
            new DateTimeImmutable('+3 days 11:00'),
        ),
        totalPrice: Money::fromCents(100000, 'RUB'),
    );
}

function makeQuantityBookingForCancel(UserId $userId): Booking
{
    return Booking::createQuantityBooking(
        id: BookingId::generate(),
        userId: $userId,
        serviceId: ServiceId::generate(),
        dateRange: DateRange::fromStrings('2026-05-01', '2026-05-03'),
        quantity: new Quantity(1),
        totalPrice: Money::fromCents(500000, 'RUB'),
    );
}

it('cancels TIME_SLOT booking, releases slot, dispatches events', function (): void {
    $userId = UserId::generate();
    $slotId = SlotId::generate();
    $booking = makeTimeSlotBookingForCancel($userId, $slotId);
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);
    $bookingRepo->shouldReceive('save')->once();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('markAsFree')
        ->once()
        ->with(\Mockery::on(fn (SlotId $id): bool => $id->toString() === $slotId->toString()));

    $policy = mock(CancellationPolicy::class);
    $policy->shouldReceive('isSatisfiedBy')->andReturnTrue();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new CancelBookingHandler($bookingRepo, $slotRepo, $policy, $dispatcher);
    $handler->handle(new CancelBookingCommand(
        bookingId: $booking->id->toString(),
        actorUserId: $userId->toString(),
    ));
});

it('throws BookingNotFoundException when booking is missing', function (): void {
    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturnNull();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $policy = mock(CancellationPolicy::class);
    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CancelBookingHandler($bookingRepo, $slotRepo, $policy, $dispatcher);
    $handler->handle(new CancelBookingCommand(
        bookingId: BookingId::generate()->toString(),
        actorUserId: UserId::generate()->toString(),
    ));
})->throws(BookingNotFoundException::class);

it('throws CancellationNotAllowedException when policy fails', function (): void {
    $userId = UserId::generate();
    $booking = makeTimeSlotBookingForCancel($userId, SlotId::generate());
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $slotRepo = mock(TimeSlotRepositoryInterface::class);

    $policy = mock(CancellationPolicy::class);
    $policy->shouldReceive('isSatisfiedBy')->andReturnFalse();
    $policy->shouldReceive('failureReason')->andReturn('outside cancellation window');

    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CancelBookingHandler($bookingRepo, $slotRepo, $policy, $dispatcher);
    $handler->handle(new CancelBookingCommand(
        bookingId: $booking->id->toString(),
        actorUserId: $userId->toString(),
    ));
})->throws(CancellationNotAllowedException::class);

it('throws RuntimeException when non-admin user tries to cancel someone elses booking', function (): void {
    $ownerUserId = UserId::generate();
    $otherUserId = UserId::generate();
    $booking = makeTimeSlotBookingForCancel($ownerUserId, SlotId::generate());
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $policy = mock(CancellationPolicy::class);
    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CancelBookingHandler($bookingRepo, $slotRepo, $policy, $dispatcher);
    $handler->handle(new CancelBookingCommand(
        bookingId: $booking->id->toString(),
        actorUserId: $otherUserId->toString(),
    ));
})->throws(RuntimeException::class, 'Forbidden cancellation');

it('does not call markAsFree for QUANTITY bookings', function (): void {
    $userId = UserId::generate();
    $booking = makeQuantityBookingForCancel($userId);
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);
    $bookingRepo->shouldReceive('save')->once();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldNotReceive('markAsFree');

    $policy = mock(CancellationPolicy::class);
    $policy->shouldReceive('isSatisfiedBy')->andReturnTrue();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new CancelBookingHandler($bookingRepo, $slotRepo, $policy, $dispatcher);
    $handler->handle(new CancelBookingCommand(
        bookingId: $booking->id->toString(),
        actorUserId: $userId->toString(),
    ));
});

it('allows admin to cancel any booking', function (): void {
    $ownerUserId = UserId::generate();
    $adminUserId = UserId::generate();
    $booking = makeQuantityBookingForCancel($ownerUserId);
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);
    $bookingRepo->shouldReceive('save')->once();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);

    $policy = mock(CancellationPolicy::class);
    $policy->shouldReceive('isSatisfiedBy')->andReturnTrue();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new CancelBookingHandler($bookingRepo, $slotRepo, $policy, $dispatcher);
    $handler->handle(new CancelBookingCommand(
        bookingId: $booking->id->toString(),
        actorUserId: $adminUserId->toString(),
        isAdmin: true,
    ));
});
