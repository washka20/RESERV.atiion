<?php

declare(strict_types=1);

use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Application\Query\GetBookingById\GetBookingByIdQuery;
use App\Modules\Booking\Domain\Event\BookingCreated;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Application\Command\InitiatePayment\InitiatePaymentCommand;
use App\Modules\Payment\Application\Listener\InitiatePaymentOnBookingCreated;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;

it('dispatches InitiatePaymentCommand with booking price and currency', function (): void {
    $bookingId = BookingId::generate();
    $serviceId = ServiceId::generate();
    $userId = UserId::generate();
    $event = new BookingCreated($bookingId, $userId, $serviceId, BookingType::TIME_SLOT, new DateTimeImmutable);

    $dto = new BookingDTO(
        id: $bookingId->toString(),
        userId: $userId->toString(),
        serviceId: $serviceId->toString(),
        type: 'time_slot',
        status: 'pending',
        slotId: null,
        startAt: null,
        endAt: null,
        checkIn: null,
        checkOut: null,
        quantity: null,
        totalPriceAmount: 240000,
        totalPriceCurrency: 'RUB',
        notes: null,
        createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
        updatedAt: (new DateTimeImmutable)->format(DATE_ATOM),
    );

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')
        ->once()
        ->with(Mockery::on(fn (GetBookingByIdQuery $q) => $q->bookingId === $bookingId->toString()))
        ->andReturn($dto);

    $captured = null;
    $commands = mock(CommandBusInterface::class);
    $commands->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (InitiatePaymentCommand $cmd) use (&$captured): void {
            $captured = $cmd;
        });

    $listener = new InitiatePaymentOnBookingCreated($commands, $queries);
    $listener->handle($event);

    expect($captured)->not->toBeNull()
        ->and($captured->bookingId->equals($bookingId))->toBeTrue()
        ->and($captured->gross->amount())->toBe(240000)
        ->and($captured->gross->currency())->toBe('RUB')
        ->and($captured->method)->toBe(PaymentMethod::CARD);
});

it('is no-op when booking not found', function (): void {
    $bookingId = BookingId::generate();
    $serviceId = ServiceId::generate();
    $userId = UserId::generate();
    $event = new BookingCreated($bookingId, $userId, $serviceId, BookingType::TIME_SLOT, new DateTimeImmutable);

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')->once()->andReturn(null);

    $commands = mock(CommandBusInterface::class);
    $commands->shouldNotReceive('dispatch');

    $listener = new InitiatePaymentOnBookingCreated($commands, $queries);
    $listener->handle($event);
});
