<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\ConfirmBooking\ConfirmBookingCommand;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Application\Listener\ConfirmBookingOnPaymentReceived;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Shared\Application\Bus\CommandBusInterface;

it('dispatches ConfirmBookingCommand with booking id from event', function (): void {
    $bookingId = BookingId::generate();
    $paymentId = PaymentId::generate();
    $event = new PaymentReceived(
        $paymentId,
        $bookingId,
        Money::fromCents(100000),
        Money::fromCents(10000),
        Money::fromCents(90000),
        'ref-1',
        new DateTimeImmutable,
    );

    $captured = null;
    $commands = mock(CommandBusInterface::class);
    $commands->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (ConfirmBookingCommand $cmd) use (&$captured): void {
            $captured = $cmd;
        });

    $listener = new ConfirmBookingOnPaymentReceived($commands);
    $listener->handle($event);

    expect($captured)->not->toBeNull()
        ->and($captured->bookingId)->toBe($bookingId->toString());
});
