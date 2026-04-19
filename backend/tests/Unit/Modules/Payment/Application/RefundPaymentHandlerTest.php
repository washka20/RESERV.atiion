<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Application\Command\RefundPayment\RefundPaymentCommand;
use App\Modules\Payment\Application\Command\RefundPayment\RefundPaymentHandler;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Event\PaymentRefunded;
use App\Modules\Payment\Domain\Exception\PaymentAlreadyProcessedException;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

function makePaidPaymentForRefund(PaymentId $id): Payment
{
    $payment = Payment::initiate(
        $id,
        BookingId::generate(),
        Money::fromCents(100000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->markPaid('provider-ref');
    $payment->pullDomainEvents();

    return $payment;
}

it('refunds PAID payment and publishes PaymentRefunded with reliable=true', function (): void {
    $id = PaymentId::generate();
    $payment = makePaidPaymentForRefund($id);

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($payment);
    $repo->shouldReceive('save')->once();

    $publishedEvent = null;
    $publishedReliable = null;
    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')
        ->once()
        ->andReturnUsing(function ($event, bool $reliable) use (&$publishedEvent, &$publishedReliable): void {
            $publishedEvent = $event;
            $publishedReliable = $reliable;
        });

    $handler = new RefundPaymentHandler($repo, $publisher, new PassthroughTransactionManager);
    $dto = $handler->handle(new RefundPaymentCommand($id));

    expect($payment->status())->toBe(PaymentStatus::REFUNDED)
        ->and($publishedEvent)->toBeInstanceOf(PaymentRefunded::class)
        ->and($publishedReliable)->toBeTrue()
        ->and($dto->status)->toBe(PaymentStatus::REFUNDED->value);
});

it('throws on refund of non-paid payment', function (): void {
    $id = PaymentId::generate();
    $pendingPayment = Payment::initiate(
        $id,
        BookingId::generate(),
        Money::fromCents(100000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $pendingPayment->pullDomainEvents();

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($pendingPayment);

    $publisher = mock(OutboxPublisherInterface::class);

    $handler = new RefundPaymentHandler($repo, $publisher, new PassthroughTransactionManager);
    $handler->handle(new RefundPaymentCommand($id));
})->throws(PaymentAlreadyProcessedException::class);

it('throws when payment not found on Refund', function (): void {
    $id = PaymentId::generate();

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $publisher = mock(OutboxPublisherInterface::class);

    $handler = new RefundPaymentHandler($repo, $publisher, new PassthroughTransactionManager);
    $handler->handle(new RefundPaymentCommand($id));
})->throws(RuntimeException::class);
