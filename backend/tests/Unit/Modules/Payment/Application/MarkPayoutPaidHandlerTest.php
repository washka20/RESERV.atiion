<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\Command\MarkPayoutPaid\MarkPayoutPaidCommand;
use App\Modules\Payment\Application\Command\MarkPayoutPaid\MarkPayoutPaidHandler;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Event\PayoutMarkedPaid;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

function makePendingPayout(PayoutTransactionId $id): PayoutTransaction
{
    $payout = PayoutTransaction::create(
        $id,
        BookingId::generate(),
        OrganizationId::generate(),
        PaymentId::generate(),
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $payout->pullDomainEvents();

    return $payout;
}

it('moves PENDING payout to PAID and publishes PayoutMarkedPaid reliable=true', function (): void {
    $id = PayoutTransactionId::generate();
    $payout = makePendingPayout($id);

    $repo = mock(PayoutTransactionRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn($payout);
    $repo->shouldReceive('save')->once();

    $publishedEvent = null;
    $publishedReliable = null;
    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')->once()->andReturnUsing(
        function ($event, bool $reliable) use (&$publishedEvent, &$publishedReliable): void {
            $publishedEvent = $event;
            $publishedReliable = $reliable;
        }
    );

    $handler = new MarkPayoutPaidHandler($repo, $publisher, new PassthroughTransactionManager);
    $dto = $handler->handle(new MarkPayoutPaidCommand($id->toString()));

    expect($payout->status())->toBe(PayoutStatus::PAID)
        ->and($publishedEvent)->toBeInstanceOf(PayoutMarkedPaid::class)
        ->and($publishedReliable)->toBeTrue()
        ->and($dto->status)->toBe(PayoutStatus::PAID->value);
});

it('throws when payout not found', function (): void {
    $id = PayoutTransactionId::generate();

    $repo = mock(PayoutTransactionRepositoryInterface::class);
    $repo->shouldReceive('findById')->once()->andReturn(null);

    $publisher = mock(OutboxPublisherInterface::class);

    $handler = new MarkPayoutPaidHandler($repo, $publisher, new PassthroughTransactionManager);
    $handler->handle(new MarkPayoutPaidCommand($id->toString()));
})->throws(RuntimeException::class);
