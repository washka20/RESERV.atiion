<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\MarkPaymentPaid;

use App\Modules\Payment\Application\DTO\PaymentDTO;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Handler MarkPaymentPaidCommand.
 *
 * Применяет markPaid к доменной сущности, сохраняет и публикует PaymentReceived
 * в транзакционный outbox (reliable=true) — событие гарантированно получат downstream
 * подписчики (Payouts, уведомления) после commit.
 */
final readonly class MarkPaymentPaidHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repo,
        private OutboxPublisherInterface $publisher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(MarkPaymentPaidCommand $cmd): PaymentDTO
    {
        return $this->tx->transactional(function () use ($cmd): PaymentDTO {
            $payment = $this->repo->findById($cmd->id);
            if ($payment === null) {
                throw new RuntimeException(sprintf('Payment %s not found', $cmd->id->toString()));
            }

            $payment->markPaid($cmd->providerRef);
            $this->repo->save($payment);

            foreach ($payment->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: true);
            }

            return PaymentDTO::fromEntity($payment);
        });
    }
}
