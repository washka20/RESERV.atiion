<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\InitiatePayment;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;

/**
 * Команда «инициировать платёж» для бронирования.
 *
 * Создаёт Payment в статусе PENDING, публикует PaymentInitiated, запускает charge через
 * платёжный шлюз и диспатчит follow-up MarkPaymentPaidCommand / MarkPaymentFailedCommand
 * через CommandBus — два шага чтобы each step имеет свой transactional boundary.
 */
final readonly class InitiatePaymentCommand
{
    public function __construct(
        public BookingId $bookingId,
        public Money $gross,
        public PaymentMethod $method,
    ) {}
}
