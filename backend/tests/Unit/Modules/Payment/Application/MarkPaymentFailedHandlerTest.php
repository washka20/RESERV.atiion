<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Application\Command\MarkPaymentFailed\MarkPaymentFailedCommand;
use App\Modules\Payment\Application\Command\MarkPaymentFailed\MarkPaymentFailedHandler;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Event\PaymentFailed;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

it('marks payment failed and publishes PaymentFailed with reliable=false', function (): void {
    $id = PaymentId::generate();
    $payment = Payment::initiate(
        $id,
        BookingId::generate(),
        Money::fromCents(100000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
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

    $handler = new MarkPaymentFailedHandler($repo, $publisher, new PassthroughTransactionManager);
    $dto = $handler->handle(new MarkPaymentFailedCommand($id, 'card expired'));

    expect($payment->status())->toBe(PaymentStatus::FAILED)
        ->and($publishedEvent)->toBeInstanceOf(PaymentFailed::class)
        ->and($publishedReliable)->toBeFalse()
        ->and($dto->status)->toBe(PaymentStatus::FAILED->value);
});

it('throws when payment not found on MarkFailed', function (): void {
    $id = PaymentId::generate();

    $repo = mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $publisher = mock(OutboxPublisherInterface::class);

    $handler = new MarkPaymentFailedHandler($repo, $publisher, new PassthroughTransactionManager);
    $handler->handle(new MarkPaymentFailedCommand($id, 'reason'));
})->throws(RuntimeException::class);
