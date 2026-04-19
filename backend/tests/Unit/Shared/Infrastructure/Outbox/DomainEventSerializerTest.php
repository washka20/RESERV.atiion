<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Event\BookingCancelled;
use App\Modules\Booking\Domain\Event\BookingCreated;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Domain\Event\PaymentInitiated;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Shared\Infrastructure\Outbox\DomainEventSerializer;

it('round-trips PaymentReceived through toPayload/fromPayload', function (): void {
    $original = new PaymentReceived(
        PaymentId::generate(),
        BookingId::generate(),
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
        'prov-ref-xyz',
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );

    $serializer = new DomainEventSerializer;

    $stored = $serializer->toPayload($original);
    expect($stored)->toHaveKey('event_class', PaymentReceived::class);
    expect($stored)->toHaveKey('event_name', 'payment.received');
    expect($stored['data'])->toBe($original->payload());

    $restored = $serializer->fromPayload($stored);
    expect($restored)->toBeInstanceOf(PaymentReceived::class);
    expect($restored->payload())->toBe($original->payload());
    expect($restored->aggregateId())->toBe($original->aggregateId());
    expect($restored->occurredAt()->format(DATE_ATOM))->toBe($original->occurredAt()->format(DATE_ATOM));
});

it('round-trips PaymentInitiated preserving enum and percentage', function (): void {
    $original = new PaymentInitiated(
        PaymentId::generate(),
        BookingId::generate(),
        Money::fromCents(55_000, 'USD'),
        PaymentMethod::SBP,
        Percentage::fromInt(7),
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );

    $serializer = new DomainEventSerializer;
    $restored = $serializer->fromPayload($serializer->toPayload($original));

    expect($restored)->toBeInstanceOf(PaymentInitiated::class);
    /** @var PaymentInitiated $restored */
    expect($restored->method())->toBe(PaymentMethod::SBP);
    expect($restored->feePercent()->value())->toBe(7);
    expect($restored->gross()->currency())->toBe('USD');
    expect($restored->payload())->toBe($original->payload());
});

it('round-trips BookingCreated across modules', function (): void {
    $original = new BookingCreated(
        BookingId::generate(),
        UserId::generate(),
        ServiceId::generate(),
        BookingType::QUANTITY,
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );

    $serializer = new DomainEventSerializer;
    $restored = $serializer->fromPayload($serializer->toPayload($original));

    expect($restored)->toBeInstanceOf(BookingCreated::class);
    expect($restored->payload())->toBe($original->payload());
});

it('round-trips BookingCancelled with nullable slotId', function (): void {
    $withSlot = new BookingCancelled(
        BookingId::generate(),
        BookingType::TIME_SLOT,
        SlotId::generate(),
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );

    $withoutSlot = new BookingCancelled(
        BookingId::generate(),
        BookingType::QUANTITY,
        null,
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );

    $serializer = new DomainEventSerializer;

    $r1 = $serializer->fromPayload($serializer->toPayload($withSlot));
    $r2 = $serializer->fromPayload($serializer->toPayload($withoutSlot));

    expect($r1->payload())->toBe($withSlot->payload());
    expect($r2->payload())->toBe($withoutSlot->payload());
});

it('throws when event_class is missing', function (): void {
    (new DomainEventSerializer)->fromPayload(['data' => []]);
})->throws(RuntimeException::class, 'missing event_class');

it('throws when event_class is not a DomainEvent', function (): void {
    (new DomainEventSerializer)->fromPayload([
        'event_class' => stdClass::class,
        'data' => [],
    ]);
})->throws(RuntimeException::class, 'unknown event class');
