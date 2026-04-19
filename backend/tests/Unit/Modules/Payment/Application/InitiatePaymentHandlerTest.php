<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Application\Command\InitiatePayment\InitiatePaymentCommand;
use App\Modules\Payment\Application\Command\InitiatePayment\InitiatePaymentHandler;
use App\Modules\Payment\Application\Command\MarkPaymentFailed\MarkPaymentFailedCommand;
use App\Modules\Payment\Application\Command\MarkPaymentPaid\MarkPaymentPaidCommand;
use App\Modules\Payment\Application\DTO\PaymentDTO;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Event\PaymentInitiated;
use App\Modules\Payment\Domain\Gateway\GatewayChargeResult;
use App\Modules\Payment\Domain\Gateway\PaymentGatewayInterface;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

it('initiates payment, publishes PaymentInitiated and dispatches MarkPaid on gateway success', function (): void {
    $bookingId = BookingId::generate();
    $gross = Money::fromCents(100000, 'RUB');

    /** @var Payment|null $savedPayment */
    $savedPayment = null;
    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('save')->once()->andReturnUsing(function (Payment $p) use (&$savedPayment): void {
        $savedPayment = $p;
    });

    $gateway = mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('createCharge')
        ->once()
        ->with($gross, Mockery::on(fn (BookingId $b) => $b->equals($bookingId)), PaymentMethod::CARD)
        ->andReturn(new GatewayChargeResult(true, 'provider-xyz', null));

    $initiatedPublished = false;
    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')
        ->once()
        ->andReturnUsing(function ($event, bool $reliable) use (&$initiatedPublished): void {
            expect($event)->toBeInstanceOf(PaymentInitiated::class);
            expect($reliable)->toBeFalse();
            $initiatedPublished = true;
        });

    $markPaidDispatched = false;
    $commandBus = mock(CommandBusInterface::class);
    $commandBus->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (object $cmd) use (&$markPaidDispatched): PaymentDTO {
            expect($cmd)->toBeInstanceOf(MarkPaymentPaidCommand::class);
            /** @var MarkPaymentPaidCommand $cmd */
            expect($cmd->providerRef)->toBe('provider-xyz');
            $markPaidDispatched = true;

            return new PaymentDTO(
                id: $cmd->id->toString(),
                bookingId: 'b',
                amountCents: 100000,
                currency: 'RUB',
                status: PaymentStatus::PAID->value,
                method: PaymentMethod::CARD->value,
                feePercent: 10,
                platformFeeCents: 10000,
                netAmountCents: 90000,
                providerRef: 'provider-xyz',
                paidAt: (new DateTimeImmutable)->format(DATE_ATOM),
                createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
            );
        });

    $handler = new InitiatePaymentHandler(
        $repo,
        $gateway,
        $publisher,
        $commandBus,
        new PassthroughTransactionManager,
        feePercent: 10,
    );

    $dto = $handler->handle(new InitiatePaymentCommand($bookingId, $gross, PaymentMethod::CARD));

    expect($savedPayment)->not->toBeNull()
        ->and($savedPayment->status())->toBe(PaymentStatus::PENDING)
        ->and($savedPayment->bookingId()->equals($bookingId))->toBeTrue()
        ->and($savedPayment->feePercent()->value())->toBe(10)
        ->and($initiatedPublished)->toBeTrue()
        ->and($markPaidDispatched)->toBeTrue()
        ->and($dto->status)->toBe(PaymentStatus::PAID->value);
});

it('dispatches MarkPaymentFailed when gateway declines', function (): void {
    $bookingId = BookingId::generate();
    $gross = Money::fromCents(50000, 'RUB');

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('save')->once();

    $gateway = mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('createCharge')
        ->once()
        ->andReturn(new GatewayChargeResult(false, null, 'card declined'));

    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')->once();

    $markFailedDispatched = false;
    $commandBus = mock(CommandBusInterface::class);
    $commandBus->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (object $cmd) use (&$markFailedDispatched): PaymentDTO {
            expect($cmd)->toBeInstanceOf(MarkPaymentFailedCommand::class);
            /** @var MarkPaymentFailedCommand $cmd */
            expect($cmd->reason)->toBe('card declined');
            $markFailedDispatched = true;

            return new PaymentDTO(
                id: $cmd->id->toString(),
                bookingId: 'b',
                amountCents: 50000,
                currency: 'RUB',
                status: PaymentStatus::FAILED->value,
                method: PaymentMethod::CARD->value,
                feePercent: 10,
                platformFeeCents: 5000,
                netAmountCents: 45000,
                providerRef: null,
                paidAt: null,
                createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
            );
        });

    $handler = new InitiatePaymentHandler(
        $repo,
        $gateway,
        $publisher,
        $commandBus,
        new PassthroughTransactionManager,
        feePercent: 10,
    );

    $dto = $handler->handle(new InitiatePaymentCommand($bookingId, $gross, PaymentMethod::CARD));

    expect($markFailedDispatched)->toBeTrue()
        ->and($dto->status)->toBe(PaymentStatus::FAILED->value);
});

it('uses default error message when gateway returns null errorMessage', function (): void {
    $bookingId = BookingId::generate();
    $gross = Money::fromCents(10000, 'RUB');

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('save')->once();

    $gateway = mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('createCharge')
        ->once()
        ->andReturn(new GatewayChargeResult(false, null, null));

    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')->once();

    $capturedReason = null;
    $commandBus = mock(CommandBusInterface::class);
    $commandBus->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (MarkPaymentFailedCommand $cmd) use (&$capturedReason): PaymentDTO {
            $capturedReason = $cmd->reason;

            return new PaymentDTO(
                id: $cmd->id->toString(),
                bookingId: 'b',
                amountCents: 10000,
                currency: 'RUB',
                status: PaymentStatus::FAILED->value,
                method: PaymentMethod::CARD->value,
                feePercent: 10,
                platformFeeCents: 1000,
                netAmountCents: 9000,
                providerRef: null,
                paidAt: null,
                createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
            );
        });

    $handler = new InitiatePaymentHandler(
        $repo,
        $gateway,
        $publisher,
        $commandBus,
        new PassthroughTransactionManager,
        feePercent: 10,
    );
    $handler->handle(new InitiatePaymentCommand($bookingId, $gross, PaymentMethod::CARD));

    expect($capturedReason)->toBe('gateway declined');
});
