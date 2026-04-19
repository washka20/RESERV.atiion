<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Listener;

use App\Modules\Booking\Application\Query\GetBookingById\GetBookingByIdQuery;
use App\Modules\Booking\Domain\Event\BookingCreated;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Application\Command\InitiatePayment\InitiatePaymentCommand;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;

/**
 * Listener события BookingCreated — стартует процесс оплаты.
 *
 * Читает booking через GetBookingByIdQuery (нужны totalPrice + currency —
 * BookingCreated сам не содержит amount). Dispatch InitiatePaymentCommand
 * с PaymentMethod::CARD по умолчанию (MVP — без выбора метода на стороне user).
 *
 * Идемпотентен: если booking уже удалён (кейс неудачи) — просто no-op.
 */
final readonly class InitiatePaymentOnBookingCreated
{
    public function __construct(
        private CommandBusInterface $commands,
        private QueryBusInterface $queries,
    ) {}

    public function handle(BookingCreated $event): void
    {
        $booking = $this->queries->ask(
            new GetBookingByIdQuery($event->bookingId()->toString()),
        );
        if ($booking === null) {
            return;
        }

        $this->commands->dispatch(new InitiatePaymentCommand(
            bookingId: $event->bookingId(),
            gross: Money::fromCents($booking->totalPriceAmount, $booking->totalPriceCurrency),
            method: PaymentMethod::CARD,
        ));
    }
}
