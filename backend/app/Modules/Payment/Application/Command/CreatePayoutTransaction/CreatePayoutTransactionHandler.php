<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\CreatePayoutTransaction;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\DTO\PayoutTransactionDTO;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\MarketplaceFee;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * Handler CreatePayoutTransactionCommand.
 *
 * Idempotent flow:
 *  1. Проверка findByBookingId — если выплата уже создана → return null (повторное событие).
 *  2. Рассчитывает fee/net через MarketplaceFee VO с feePercent из config('payments.marketplace_fee_percent').
 *  3. В транзакции: save + publish PayoutTransactionCreated (reliable=false —
 *     подписчиков на создание нет в MVP, внутрисистемное состояние).
 */
final readonly class CreatePayoutTransactionHandler
{
    public function __construct(
        private PayoutTransactionRepositoryInterface $repo,
        private OutboxPublisherInterface $publisher,
        private TransactionManagerInterface $tx,
        private int $feePercent,
    ) {}

    public function handle(CreatePayoutTransactionCommand $cmd): ?PayoutTransactionDTO
    {
        $bookingId = new BookingId($cmd->bookingId);

        if ($this->repo->findByBookingId($bookingId) !== null) {
            return null;
        }

        $gross = Money::fromCents($cmd->grossCents, $cmd->currency);
        $fee = MarketplaceFee::calculate($gross, Percentage::fromInt($this->feePercent));

        return $this->tx->transactional(function () use ($cmd, $bookingId, $gross, $fee): PayoutTransactionDTO {
            $payout = PayoutTransaction::create(
                PayoutTransactionId::generate(),
                $bookingId,
                new OrganizationId($cmd->organizationId),
                new PaymentId($cmd->paymentId),
                $gross,
                $fee->fee(),
                $fee->net(),
            );

            $this->repo->save($payout);

            foreach ($payout->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: false);
            }

            return PayoutTransactionDTO::fromEntity($payout);
        });
    }
}
