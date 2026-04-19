<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\MarkPayoutPaid;

use App\Modules\Payment\Application\DTO\PayoutTransactionDTO;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Handler MarkPayoutPaidCommand.
 *
 * Переводит payout PENDING → PROCESSING → PAID, сохраняет и публикует PayoutMarkedPaid
 * в транзакционный outbox (reliable=true) — downstream подписчики получат событие
 * гарантированно после commit.
 */
final readonly class MarkPayoutPaidHandler
{
    public function __construct(
        private PayoutTransactionRepositoryInterface $repo,
        private OutboxPublisherInterface $publisher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(MarkPayoutPaidCommand $cmd): PayoutTransactionDTO
    {
        return $this->tx->transactional(function () use ($cmd): PayoutTransactionDTO {
            $payout = $this->repo->findById(new PayoutTransactionId($cmd->payoutId));
            if ($payout === null) {
                throw new RuntimeException(sprintf('Payout %s not found', $cmd->payoutId));
            }

            if ($payout->status() === PayoutStatus::PENDING) {
                $payout->moveToProcessing();
            }

            $payout->markPaid();
            $this->repo->save($payout);

            foreach ($payout->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: true);
            }

            return PayoutTransactionDTO::fromEntity($payout);
        });
    }
}
