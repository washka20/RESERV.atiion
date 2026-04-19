<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Listener;

use App\Modules\Booking\Application\Command\ConfirmBooking\ConfirmBookingCommand;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Shared\Application\Bus\CommandBusInterface;

/**
 * Listener события PaymentReceived — автоподтверждает booking.
 *
 * Часть happy-path: payment.paid → booking.confirmed.
 * Если booking уже confirmed/cancelled — ConfirmBookingHandler сам обработает
 * (идемпотентность + валидация статуса на уровне домена).
 */
final readonly class ConfirmBookingOnPaymentReceived
{
    public function __construct(
        private CommandBusInterface $commands,
    ) {}

    public function handle(PaymentReceived $event): void
    {
        $this->commands->dispatch(new ConfirmBookingCommand(
            $event->bookingId()->toString(),
        ));
    }
}
