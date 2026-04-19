<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Event\PaymentFailed;
use App\Modules\Payment\Domain\Event\PaymentInitiated;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\Event\PaymentRefunded;
use App\Modules\Payment\Domain\Exception\InvalidPaymentAmountException;
use App\Modules\Payment\Domain\Exception\PaymentAlreadyProcessedException;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;

function paymentTestIds(): array
{
    return [
        'payment' => PaymentId::generate(),
        'booking' => BookingId::generate(),
    ];
}

it('initiates payment in PENDING status', function () {
    $ids = paymentTestIds();

    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );

    expect($payment->id())->toBe($ids['payment']);
    expect($payment->bookingId())->toBe($ids['booking']);
    expect($payment->gross()->amount())->toBe(100_000);
    expect($payment->method())->toBe(PaymentMethod::CARD);
    expect($payment->status())->toBe(PaymentStatus::PENDING);
    expect($payment->feePercent()->value())->toBe(10);
    expect($payment->platformFee()->amount())->toBe(10_000);
    expect($payment->net()->amount())->toBe(90_000);
    expect($payment->providerRef())->toBeNull();
    expect($payment->paidAt())->toBeNull();
});

it('records PaymentInitiated event on initiate', function () {
    $ids = paymentTestIds();

    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(50_000),
        PaymentMethod::SBP,
        Percentage::fromInt(5),
    );

    $events = $payment->pullDomainEvents();

    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(PaymentInitiated::class);
    expect($events[0]->eventName())->toBe('payment.initiated');
    expect($events[0]->aggregateId())->toBe($ids['payment']->toString());
});

it('throws InvalidPaymentAmountException when gross is zero', function () {
    $ids = paymentTestIds();

    Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(0),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
})->throws(InvalidPaymentAmountException::class);

it('marks payment as PAID with provider ref', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->pullDomainEvents();

    $payment->markPaid('provider-ref-123');

    expect($payment->status())->toBe(PaymentStatus::PAID);
    expect($payment->providerRef())->toBe('provider-ref-123');
    expect($payment->paidAt())->toBeInstanceOf(DateTimeImmutable::class);

    $events = $payment->pullDomainEvents();
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(PaymentReceived::class);
    expect($events[0]->eventName())->toBe('payment.received');
    expect($events[0]->platformFee()->amount())->toBe(10_000);
    expect($events[0]->net()->amount())->toBe(90_000);
    expect($events[0]->providerRef())->toBe('provider-ref-123');
});

it('throws when marking PAID a payment that is already paid', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->markPaid('provider-ref-1');

    $payment->markPaid('provider-ref-2');
})->throws(PaymentAlreadyProcessedException::class);

it('marks payment as FAILED with reason', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->pullDomainEvents();

    $payment->markFailed('gateway-timeout');

    expect($payment->status())->toBe(PaymentStatus::FAILED);

    $events = $payment->pullDomainEvents();
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(PaymentFailed::class);
    expect($events[0]->reason())->toBe('gateway-timeout');
    expect($events[0]->eventName())->toBe('payment.failed');
});

it('throws when marking FAILED a payment that is already paid', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->markPaid('ref');

    $payment->markFailed('too-late');
})->throws(PaymentAlreadyProcessedException::class);

it('refunds a PAID payment', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->markPaid('ref');
    $payment->pullDomainEvents();

    $payment->refund();

    expect($payment->status())->toBe(PaymentStatus::REFUNDED);

    $events = $payment->pullDomainEvents();
    expect($events)->toHaveCount(1);
    expect($events[0])->toBeInstanceOf(PaymentRefunded::class);
    expect($events[0]->eventName())->toBe('payment.refunded');
    expect($events[0]->amount()->amount())->toBe(100_000);
});

it('throws when refunding a PENDING payment', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );

    $payment->refund();
})->throws(PaymentAlreadyProcessedException::class);

it('throws when refunding an already refunded payment', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->markPaid('ref');
    $payment->refund();

    $payment->refund();
})->throws(PaymentAlreadyProcessedException::class);

it('throws when refunding a FAILED payment', function () {
    $ids = paymentTestIds();
    $payment = Payment::initiate(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    $payment->markFailed('declined');

    $payment->refund();
})->throws(PaymentAlreadyProcessedException::class);

it('reconstitutes payment from persistence without recording events', function () {
    $ids = paymentTestIds();
    $paidAt = new DateTimeImmutable('2025-01-15T12:00:00+00:00');

    $payment = Payment::reconstitute(
        $ids['payment'],
        $ids['booking'],
        Money::fromCents(100_000),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
        PaymentStatus::PAID,
        'provider-ref',
        $paidAt,
    );

    expect($payment->status())->toBe(PaymentStatus::PAID);
    expect($payment->providerRef())->toBe('provider-ref');
    expect($payment->paidAt())->toEqual($paidAt);
    expect($payment->pullDomainEvents())->toBe([]);
});
