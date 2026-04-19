<?php

declare(strict_types=1);

use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Application\Query\GetBookingById\GetBookingByIdQuery;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Application\Query\GetServiceOrganizationId\GetServiceOrganizationIdQuery;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\Command\CreatePayoutTransaction\CreatePayoutTransactionCommand;
use App\Modules\Payment\Application\Listener\CreatePayoutTransactionOnPaymentReceived;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;

it('resolves org id from service and dispatches CreatePayoutTransactionCommand', function (): void {
    $bookingId = BookingId::generate();
    $serviceId = ServiceId::generate();
    $orgId = OrganizationId::generate()->toString();
    $paymentId = PaymentId::generate();

    $event = new PaymentReceived(
        $paymentId,
        $bookingId,
        Money::fromCents(240000),
        Money::fromCents(24000),
        Money::fromCents(216000),
        'ref-2',
        new DateTimeImmutable,
    );

    $dto = new BookingDTO(
        id: $bookingId->toString(),
        userId: 'u',
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
        ->with(Mockery::type(GetBookingByIdQuery::class))
        ->andReturn($dto);
    $queries->shouldReceive('ask')
        ->once()
        ->with(Mockery::on(fn (GetServiceOrganizationIdQuery $q) => $q->serviceId === $serviceId->toString()))
        ->andReturn($orgId);

    $captured = null;
    $commands = mock(CommandBusInterface::class);
    $commands->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (CreatePayoutTransactionCommand $cmd) use (&$captured): void {
            $captured = $cmd;
        });

    $listener = new CreatePayoutTransactionOnPaymentReceived($commands, $queries);
    $listener->handle($event);

    expect($captured)->not->toBeNull()
        ->and($captured->paymentId)->toBe($paymentId->toString())
        ->and($captured->bookingId)->toBe($bookingId->toString())
        ->and($captured->organizationId)->toBe($orgId)
        ->and($captured->grossCents)->toBe(240000)
        ->and($captured->currency)->toBe('RUB');
});

it('is no-op when booking is missing', function (): void {
    $event = new PaymentReceived(
        PaymentId::generate(),
        BookingId::generate(),
        Money::fromCents(100),
        Money::fromCents(10),
        Money::fromCents(90),
        'ref',
        new DateTimeImmutable,
    );

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')->once()->andReturn(null);

    $commands = mock(CommandBusInterface::class);
    $commands->shouldNotReceive('dispatch');

    (new CreatePayoutTransactionOnPaymentReceived($commands, $queries))->handle($event);
});

it('is no-op when service has no organization', function (): void {
    $event = new PaymentReceived(
        PaymentId::generate(),
        BookingId::generate(),
        Money::fromCents(100),
        Money::fromCents(10),
        Money::fromCents(90),
        'ref',
        new DateTimeImmutable,
    );

    $dto = new BookingDTO(
        id: 'b',
        userId: 'u',
        serviceId: 's',
        type: 'time_slot',
        status: 'pending',
        slotId: null,
        startAt: null,
        endAt: null,
        checkIn: null,
        checkOut: null,
        quantity: null,
        totalPriceAmount: 100,
        totalPriceCurrency: 'RUB',
        notes: null,
        createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
        updatedAt: (new DateTimeImmutable)->format(DATE_ATOM),
    );

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')->once()->andReturn($dto);
    $queries->shouldReceive('ask')->once()->andReturn(null);

    $commands = mock(CommandBusInterface::class);
    $commands->shouldNotReceive('dispatch');

    (new CreatePayoutTransactionOnPaymentReceived($commands, $queries))->handle($event);
});
