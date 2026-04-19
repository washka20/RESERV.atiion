<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\CreatePayoutTransaction;

/**
 * Команда «создать выплату провайдеру» — follow-up к PaymentReceived.
 *
 * Идемпотентна: повторный вызов с тем же bookingId не создаёт дубликат
 * (см. findByBookingId-проверку в handler'е).
 */
final readonly class CreatePayoutTransactionCommand
{
    public function __construct(
        public string $paymentId,
        public string $bookingId,
        public string $organizationId,
        public int $grossCents,
        public string $currency = 'RUB',
    ) {}
}
