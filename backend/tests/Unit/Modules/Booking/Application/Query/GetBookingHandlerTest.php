<?php

declare(strict_types=1);

use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Application\Query\GetBooking\GetBookingHandler;
use App\Modules\Booking\Application\Query\GetBooking\GetBookingQuery;
use App\Modules\Booking\Domain\Entity\Booking;
use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Domain\ValueObject\TimeRange;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;

function makeBookingWithUser(UserId $userId): Booking
{
    return Booking::createTimeSlotBooking(
        id: BookingId::generate(),
        userId: $userId,
        serviceId: ServiceId::generate(),
        slotId: SlotId::generate(),
        timeRange: new TimeRange(
            new DateTimeImmutable('+3 days 10:00'),
            new DateTimeImmutable('+3 days 11:00'),
        ),
        totalPrice: Money::fromCents(100000, 'RUB'),
    );
}

it('returns BookingDTO when actor owns the booking', function (): void {
    $userId = UserId::generate();
    $booking = makeBookingWithUser($userId);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $handler = new GetBookingHandler($bookingRepo);
    $dto = $handler->handle(new GetBookingQuery(
        bookingId: $booking->id->toString(),
        actorUserId: $userId->toString(),
        isAdmin: false,
    ));

    expect($dto)->toBeInstanceOf(BookingDTO::class)
        ->and($dto->id)->toBe($booking->id->toString())
        ->and($dto->userId)->toBe($userId->toString());
});

it('returns BookingDTO when actor is admin even if not owner', function (): void {
    $ownerId = UserId::generate();
    $adminId = UserId::generate();
    $booking = makeBookingWithUser($ownerId);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $handler = new GetBookingHandler($bookingRepo);
    $dto = $handler->handle(new GetBookingQuery(
        bookingId: $booking->id->toString(),
        actorUserId: $adminId->toString(),
        isAdmin: true,
    ));

    expect($dto->id)->toBe($booking->id->toString());
});

it('throws RuntimeException when actor is not owner and not admin', function (): void {
    $ownerId = UserId::generate();
    $otherId = UserId::generate();
    $booking = makeBookingWithUser($ownerId);

    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturn($booking);

    $handler = new GetBookingHandler($bookingRepo);
    $handler->handle(new GetBookingQuery(
        bookingId: $booking->id->toString(),
        actorUserId: $otherId->toString(),
        isAdmin: false,
    ));
})->throws(RuntimeException::class, 'Forbidden');

it('throws BookingNotFoundException when booking is missing', function (): void {
    $bookingRepo = mock(BookingRepositoryInterface::class);
    $bookingRepo->shouldReceive('findById')->andReturnNull();

    $handler = new GetBookingHandler($bookingRepo);
    $handler->handle(new GetBookingQuery(
        bookingId: BookingId::generate()->toString(),
        actorUserId: UserId::generate()->toString(),
        isAdmin: false,
    ));
})->throws(BookingNotFoundException::class);
