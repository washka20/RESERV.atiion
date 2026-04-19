<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Application\Command\CreatePayoutTransaction\CreatePayoutTransactionCommand;
use App\Modules\Payment\Application\Command\CreatePayoutTransaction\CreatePayoutTransactionHandler;
use App\Modules\Payment\Application\DTO\PayoutTransactionDTO;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Event\PayoutTransactionCreated;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use Tests\Unit\Modules\Payment\Application\PassthroughTransactionManager;

it('creates PayoutTransaction with 10% fee when no payout exists yet', function (): void {
    $paymentId = PaymentId::generate();
    $bookingId = BookingId::generate();
    $orgId = OrganizationId::generate();

    $savedPayout = null;
    $repo = mock(PayoutTransactionRepositoryInterface::class);
    $repo->shouldReceive('findByBookingId')->once()->andReturn(null);
    $repo->shouldReceive('save')->once()->andReturnUsing(function (PayoutTransaction $p) use (&$savedPayout): void {
        $savedPayout = $p;
    });

    $publishedEvent = null;
    $publishedReliable = null;
    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldReceive('publish')->once()->andReturnUsing(
        function ($event, bool $reliable) use (&$publishedEvent, &$publishedReliable): void {
            $publishedEvent = $event;
            $publishedReliable = $reliable;
        }
    );

    $handler = new CreatePayoutTransactionHandler($repo, $publisher, new PassthroughTransactionManager, feePercent: 10);
    $dto = $handler->handle(new CreatePayoutTransactionCommand(
        paymentId: $paymentId->toString(),
        bookingId: $bookingId->toString(),
        organizationId: $orgId->toString(),
        grossCents: 100_000,
    ));

    expect($dto)->toBeInstanceOf(PayoutTransactionDTO::class)
        ->and($dto->grossCents)->toBe(100_000)
        ->and($dto->platformFeeCents)->toBe(10_000)
        ->and($dto->netAmountCents)->toBe(90_000)
        ->and($dto->currency)->toBe('RUB')
        ->and($dto->status)->toBe(PayoutStatus::PENDING->value)
        ->and($savedPayout)->toBeInstanceOf(PayoutTransaction::class)
        ->and($publishedEvent)->toBeInstanceOf(PayoutTransactionCreated::class)
        ->and($publishedReliable)->toBeFalse();
});

it('is idempotent — returns null when payout for booking already exists', function (): void {
    $bookingId = BookingId::generate();
    $orgId = OrganizationId::generate();
    $paymentId = PaymentId::generate();

    $existing = PayoutTransaction::create(
        PayoutTransactionId::generate(),
        $bookingId,
        $orgId,
        $paymentId,
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );

    $repo = mock(PayoutTransactionRepositoryInterface::class);
    $repo->shouldReceive('findByBookingId')->once()->andReturn($existing);
    $repo->shouldNotReceive('save');

    $publisher = mock(OutboxPublisherInterface::class);
    $publisher->shouldNotReceive('publish');

    $handler = new CreatePayoutTransactionHandler($repo, $publisher, new PassthroughTransactionManager, feePercent: 10);
    $result = $handler->handle(new CreatePayoutTransactionCommand(
        paymentId: $paymentId->toString(),
        bookingId: $bookingId->toString(),
        organizationId: $orgId->toString(),
        grossCents: 100_000,
    ));

    expect($result)->toBeNull();
});
