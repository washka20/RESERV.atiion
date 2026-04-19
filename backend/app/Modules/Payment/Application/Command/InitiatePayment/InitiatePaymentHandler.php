<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Command\InitiatePayment;

use App\Modules\Payment\Application\Command\MarkPaymentFailed\MarkPaymentFailedCommand;
use App\Modules\Payment\Application\Command\MarkPaymentPaid\MarkPaymentPaidCommand;
use App\Modules\Payment\Application\DTO\PaymentDTO;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Gateway\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * Handler InitiatePaymentCommand.
 *
 * Flow:
 *  1. Создаёт Payment в PENDING с feePercent из config('payments.marketplace_fee_percent').
 *  2. В транзакции: save + publish PaymentInitiated (reliable=false — не критично).
 *  3. Вызывает gateway.createCharge (side-effect, НЕ в транзакции — шлюз может ответить долго).
 *  4. По результату — dispatch MarkPaymentPaidCommand или MarkPaymentFailedCommand через CommandBus:
 *     оба открывают свою транзакцию для write + outbox.
 *
 * reliable=false для PaymentInitiated: подписчиков на это событие нет в MVP.
 * MarkPaymentPaidCommand внутри публикует PaymentReceived с reliable=true.
 */
final readonly class InitiatePaymentHandler
{
    public function __construct(
        private PaymentRepositoryInterface $repo,
        private PaymentGatewayInterface $gateway,
        private OutboxPublisherInterface $publisher,
        private CommandBusInterface $commandBus,
        private TransactionManagerInterface $tx,
        private int $feePercent,
    ) {}

    public function handle(InitiatePaymentCommand $cmd): PaymentDTO
    {
        $paymentId = PaymentId::generate();

        $payment = $this->tx->transactional(function () use ($cmd, $paymentId): Payment {
            $payment = Payment::initiate(
                $paymentId,
                $cmd->bookingId,
                $cmd->gross,
                $cmd->method,
                Percentage::fromInt($this->feePercent),
            );

            $this->repo->save($payment);

            foreach ($payment->pullDomainEvents() as $event) {
                $this->publisher->publish($event, reliable: false);
            }

            return $payment;
        });

        $result = $this->gateway->createCharge($cmd->gross, $cmd->bookingId, $cmd->method);

        if ($result->success && $result->providerRef !== null) {
            return $this->commandBus->dispatch(new MarkPaymentPaidCommand($paymentId, $result->providerRef));
        }

        return $this->commandBus->dispatch(new MarkPaymentFailedCommand(
            $paymentId,
            $result->errorMessage ?? 'gateway declined',
        ));
    }
}
