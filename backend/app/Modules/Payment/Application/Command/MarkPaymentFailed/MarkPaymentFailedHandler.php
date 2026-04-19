<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\MarkPaymentFailed;

use App\Modules\Payment\Application\DTO\PaymentDTO;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Handler MarkPaymentFailedCommand.
 *
 * PaymentFailed публикуется в fire-and-forget режиме (reliable=false) — failure
 * приводит только к уведомлениям пользователя, downstream-сервисов на этот статус
 * подписанных нет (payout idempotent по paid статусу).
 */
final readonly class MarkPaymentFailedHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repo,
        private OutboxPublisherInterface $publisher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(MarkPaymentFailedCommand $cmd): PaymentDTO
    {
        return $this->tx->transactional(function () use ($cmd): PaymentDTO {
            $payment = $this->repo->findById($cmd->id);
            if ($payment === null) {
                throw new RuntimeException(sprintf('Payment %s not found', $cmd->id->toString()));
            }

            $payment->markFailed($cmd->reason);
            $this->repo->save($payment);

            foreach ($payment->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: false);
            }

            return PaymentDTO::fromEntity($payment);
        });
    }
}
