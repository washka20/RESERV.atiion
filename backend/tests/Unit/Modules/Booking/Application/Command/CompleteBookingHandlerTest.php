<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\CompleteBooking\CompleteBookingCommand;
use App\Modules\Booking\Application\Command\CompleteBooking\CompleteBookingHandler;
use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Exception\InvalidBookingStateTransitionException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

function makeBookingForComplete(): Booking
{
    return Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('-1 hour'),
            new DateTimeImmutable('+1 hour'),
        ),
        totalPrice: Money::fromCents(100000, 'RUB'),
    );
}

it('completes a confirmed booking', function (): void {
    $booking = makeBookingForComplete();
    $booking->pullDomainEvents();
    $booking->confirm();
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);
    $bookingRepo->shouldReceive('save')->once();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new CompleteBookingHandler($bookingRepo, $dispatcher);
    $handler->handle(new CompleteBookingCommand($booking->id->toString()));

    expect($booking->status())->toBe(BookingStatus::COMPLETED);
});

it('throws BookingNotFoundException when booking is missing', function (): void {
    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturnNull();

    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CompleteBookingHandler($bookingRepo, $dispatcher);
    $handler->handle(new CompleteBookingCommand(BookingId::generate()->toString()));
})->throws(BookingNotFoundException::class);

it('throws InvalidBookingStateTransitionException when booking is not confirmed', function (): void {
    $booking = makeBookingForComplete();
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new CompleteBookingHandler($bookingRepo, $dispatcher);
    $handler->handle(new CompleteBookingCommand($booking->id->toString()));
})->throws(InvalidBookingStateTransitionException::class);
