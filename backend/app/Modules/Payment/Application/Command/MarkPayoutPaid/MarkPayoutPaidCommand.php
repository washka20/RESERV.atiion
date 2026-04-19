<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\MarkPayoutPaid;

/**
 * Команда «отметить payout как выплаченный».
 *
 * Диспатчится PayoutWorker'ом после того как условия (расписание + минимум) выполнены.
 * Handler переводит PENDING → PROCESSING → PAID и публикует PayoutMarkedPaid reliable.
 */
final readonly class MarkPayoutPaidCommand
{
    public function __construct(public string $payoutId) {}
}
