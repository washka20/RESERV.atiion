<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Listener;

use App\Modules\Booking\Domain\Event\BookingCancelled;
use App\Modules\Payment\Application\Command\RefundPayment\RefundPaymentCommand;
use App\Modules\Payment\Application\Query\GetPaymentByBooking\GetPaymentByBookingQuery;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;

/**
 * Listener события BookingCancelled — инициирует возврат, если booking был оплачен.
 *
 * Возврат допустим только для платежа в статусе PAID. Для PENDING/FAILED/REFUNDED —
 * no-op (нечего возвращать или уже возвращено).
 */
final readonly class RefundPaymentOnBookingCancelled
{
    public function __construct(
        private CommandBusInterface $commands,
        private QueryBusInterface $queries,
    ) {}

    public function handle(BookingCancelled $event): void
    {
        $payment = $this->queries->ask(new GetPaymentByBookingQuery($event->bookingId()));
        if ($payment === null) {
            return;
        }
        if ($payment->status !== PaymentStatus::PAID->value) {
            return;
        }

        $this->commands->dispatch(new RefundPaymentCommand(
            new PaymentId($payment->id),
        ));
    }
}
