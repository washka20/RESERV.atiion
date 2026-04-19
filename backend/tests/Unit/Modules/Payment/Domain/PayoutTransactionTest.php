<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Event\PayoutMarkedPaid;
use App\Modules\Payment\Domain\Event\PayoutTransactionCreated;
use App\Modules\Payment\Domain\Exception\PayoutAlreadyProcessedException;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;

function payoutTestIds(): array
{
    return [
        'payout' => PayoutTransactionId::generate(),
        'booking' => BookingId::generate(),
        'organization' => OrganizationId::generate(),
        'payment' => PaymentId::generate(),
    ];
}

it('creates payout in PENDING status with gross=fee+net', function () {
    $ids = payoutTestIds();

    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );

    expect($payout->id())->toBe($ids['payout']);
    expect($payout->bookingId())->toBe($ids['booking']);
    expect($payout->organizationId())->toBe($ids['organization']);
    expect($payout->paymentId())->toBe($ids['payment']);
    expect($payout->grossAmount()->amount())->toBe(100_000);
    expect($payout->platformFee()->amount())->toBe(10_000);
    expect($payout->netAmount()->amount())->toBe(90_000);
    expect($payout->status())->toBe(PayoutStatus::PENDING);
    expect($payout->scheduledAt())->toBeNull();
    expect($payout->paidAt())->toBeNull();
    expect($payout->failureReason())->toBeNull();
});

it('records PayoutTransactionCreated event on create', function () {
    $ids = payoutTestIds();

    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );

    $events = $payout->pullDomainEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(PayoutTransactionCreated::class);
    expect($events[0]->eventName())->toBe('payout.transaction_created');
    expect($events[0]->aggregateId())->toBe($ids['payout']->toString());
    expect($events[0]->gross()->amount())->toBe(100_000);
    expect($events[0]->platformFee()->amount())->toBe(10_000);
    expect($events[0]->net()->amount())->toBe(90_000);
});

it('throws InvalidArgumentException when gross != fee + net', function () {
    $ids = payoutTestIds();

    PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(80_000),
    );
})->throws(InvalidArgumentException::class, 'gross must equal fee + net');

it('allows schedule to set scheduledAt', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $scheduledAt = new DateTimeImmutable('2026-05-01T10:00:00+00:00');

    $payout->schedule($scheduledAt);

    expect($payout->scheduledAt())->toEqual($scheduledAt);
});

it('moves payout from PENDING to PROCESSING', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $payout->pullDomainEvents();

    $payout->moveToProcessing();

    expect($payout->status())->toBe(PayoutStatus::PROCESSING);
});

it('throws when moving a PAID payout to PROCESSING', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $payout->moveToProcessing();
    $payout->markPaid();

    $payout->moveToProcessing();
})->throws(PayoutAlreadyProcessedException::class);

it('marks PROCESSING payout as PAID and records PayoutMarkedPaid event', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $payout->moveToProcessing();
    $payout->pullDomainEvents();

    $payout->markPaid();

    expect($payout->status())->toBe(PayoutStatus::PAID);
    expect($payout->paidAt())->toBeInstanceOf(DateTimeImmutable::class);

    $events = $payout->pullDomainEvents();
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(PayoutMarkedPaid::class);
    expect($events[0]->eventName())->toBe('payout.marked_paid');
    expect($events[0]->aggregateId())->toBe($ids['payout']->toString());
    expect($events[0]->netAmount()->amount())->toBe(90_000);
});

it('throws when marking PAID a payout that is still PENDING', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );

    $payout->markPaid();
})->throws(PayoutAlreadyProcessedException::class);

it('throws when marking PAID an already PAID payout', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $payout->moveToProcessing();
    $payout->markPaid();

    $payout->markPaid();
})->throws(PayoutAlreadyProcessedException::class);

it('marks PROCESSING payout as FAILED with reason', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );
    $payout->moveToProcessing();
    $payout->pullDomainEvents();

    $payout->markFailed('bank-declined');

    expect($payout->status())->toBe(PayoutStatus::FAILED);
    expect($payout->failureReason())->toBe('bank-declined');
});

it('throws when marking FAILED a PENDING payout', function () {
    $ids = payoutTestIds();
    $payout = PayoutTransaction::create(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
    );

    $payout->markFailed('too-early');
})->throws(PayoutAlreadyProcessedException::class);

it('reconstitutes payout from persistence without recording events', function () {
    $ids = payoutTestIds();
    $scheduledAt = new DateTimeImmutable('2026-05-01T10:00:00+00:00');
    $paidAt = new DateTimeImmutable('2026-05-02T10:00:00+00:00');

    $payout = PayoutTransaction::reconstitute(
        $ids['payout'],
        $ids['booking'],
        $ids['organization'],
        $ids['payment'],
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
        PayoutStatus::PAID,
        $scheduledAt,
        $paidAt,
        null,
    );

    expect($payout->status())->toBe(PayoutStatus::PAID);
    expect($payout->scheduledAt())->toEqual($scheduledAt);
    expect($payout->paidAt())->toEqual($paidAt);
    expect($payout->failureReason())->toBeNull();
    expect($payout->pullDomainEvents())->toBe([]);
});
