<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\MarkPaymentPaid;

use App\Modules\Payment\Domain\ValueObject\PaymentId;

/**
 * Команда «отметить платёж как успешно полученный».
 *
 * Идёт после callback от шлюза (или сразу после createCharge в MVP с NullPaymentGateway).
 * providerRef — идентификатор транзакции у провайдера для последующего refund/reconciliation.
 */
final readonly class MarkPaymentPaidCommand
{
    public function __construct(
        public PaymentId $id,
        public string $providerRef,
    ) {}
}
