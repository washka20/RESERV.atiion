<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Application\Command\MarkPaymentPaid\MarkPaymentPaidCommand;
use App\Modules\Payment\Application\Command\MarkPaymentPaid\MarkPaymentPaidHandler;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

it('marks payment paid, saves and publishes PaymentReceived with reliable=true', function (): void {
    $id = PaymentId::generate();
    $payment = Payment::initiate(
        $id,
        BookingId::generate(),
        Money::fromCents(100000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    // Очищаем PaymentInitiated т.к. хотим тестировать publish именно PaymentReceived
    $payment->pullDomainEvents();

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

    $handler = new MarkPaymentPaidHandler($repo, $publisher, new PassthroughTransactionManager);
    $dto = $handler->handle(new MarkPaymentPaidCommand($id, 'ref-42'));

    expect($payment->status())->toBe(PaymentStatus::PAID)
        ->and($payment->providerRef())->toBe('ref-42')
        ->and($publishedEvent)->toBeInstanceOf(PaymentReceived::class)
        ->and($publishedReliable)->toBeTrue()
        ->and($dto->status)->toBe(PaymentStatus::PAID->value)
        ->and($dto->providerRef)->toBe('ref-42');
});

it('throws when payment not found', function (): void {
    $id = PaymentId::generate();

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $publisher = mock(OutboxPublisherInterface::class);

    $handler = new MarkPaymentPaidHandler($repo, $publisher, new PassthroughTransactionManager);
    $handler->handle(new MarkPaymentPaidCommand($id, 'ref'));
})->throws(RuntimeException::class);
