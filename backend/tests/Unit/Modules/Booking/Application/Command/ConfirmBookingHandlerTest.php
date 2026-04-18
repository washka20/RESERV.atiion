<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\ConfirmBooking\ConfirmBookingCommand;
use App\Modules\Booking\Application\Command\ConfirmBooking\ConfirmBookingHandler;
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

function makePendingBooking(): Booking
{
    return Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: UserId::generate(),
        serviceId: ServiceId::generate(),
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('+3 days 10:00'),
            new DateTimeImmutable('+3 days 11:00'),
        ),
        totalPrice: Money::fromCents(100000, 'RUB'),
    );
}

it('confirms a pending booking', function (): void {
    $booking = makePendingBooking();
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);
    $bookingRepo->shouldReceive('save')->once();

    $dispatcher = mock(DomainEventDispatcherInterface::class);
    $dispatcher->shouldReceive('dispatchAll')->once();

    $handler = new ConfirmBookingHandler($bookingRepo, $dispatcher);
    $handler->handle(new ConfirmBookingCommand($booking->id->toString()));

    expect($booking->status())->toBe(BookingStatus::CONFIRMED);
});

it('throws BookingNotFoundException when booking is missing', function (): void {
    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturnNull();

    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new ConfirmBookingHandler($bookingRepo, $dispatcher);
    $handler->handle(new ConfirmBookingCommand(BookingId::generate()->toString()));
})->throws(BookingNotFoundException::class);

it('throws InvalidBookingStateTransitionException when booking is not pending', function (): void {
    $booking = makePendingBooking();
    $booking->pullDomainEvents();
    $booking->confirm();
    $booking->pullDomainEvents();

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $dispatcher = mock(DomainEventDispatcherInterface::class);

    $handler = new ConfirmBookingHandler($bookingRepo, $dispatcher);
    $handler->handle(new ConfirmBookingCommand($booking->id->toString()));
})->throws(InvalidBookingStateTransitionException::class);
