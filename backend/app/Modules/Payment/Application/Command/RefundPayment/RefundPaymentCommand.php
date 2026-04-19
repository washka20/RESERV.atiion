<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\RefundPayment;

use App\Modules\Payment\Domain\ValueObject\PaymentId;

/**
 * Команда «вернуть средства по платежу».
 *
 * Допустима только для статуса PAID. Идёт через gateway.refund + domain.refund +
 * PaymentRefunded в outbox (reliable=true) для отката payout через Payouts BC.
 */
final readonly class RefundPaymentCommand
{
    public function __construct(
        public PaymentId $id,
    ) {}
}
