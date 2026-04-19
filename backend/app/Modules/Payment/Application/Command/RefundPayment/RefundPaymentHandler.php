<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\RefundPayment;

use App\Modules\Payment\Application\DTO\PaymentDTO;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Handler RefundPaymentCommand.
 *
 * Доменная сущность валидирует допустимость refund (только из PAID).
 * PaymentRefunded идёт через транзакционный outbox (reliable=true) — Payouts BC должен
 * гарантированно получить refund для обратной корректировки баланса провайдера.
 */
final readonly class RefundPaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repo,
        private OutboxPublisherInterface $publisher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(RefundPaymentCommand $cmd): PaymentDTO
    {
        return $this->tx->transactional(function () use ($cmd): PaymentDTO {
            $payment = $this->repo->findById($cmd->id);
            if ($payment === null) {
                throw new RuntimeException(sprintf('Payment %s not found', $cmd->id->toString()));
            }

            $payment->refund();
            $this->repo->save($payment);

            foreach ($payment->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: true);
            }

            return PaymentDTO::fromEntity($payment);
        });
    }
}
