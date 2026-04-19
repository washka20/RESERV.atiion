<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Infrastructure\Outbox\OutboxMessageModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function outboxPublisher(): OutboxPublisherInterface
{
    return app(OutboxPublisherInterface::class);
}

function makePaymentReceived(): PaymentReceived
{
    return new PaymentReceived(
        PaymentId::generate(),
        BookingId::generate(),
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
        'prov-ref-abc',
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );
}

it('writes event to outbox_messages when reliable=true and does not fire laravel events', function (): void {
    Event::fake();

    $event = makePaymentReceived();
    outboxPublisher()->publish($event, reliable: true);

    $row = OutboxMessageModel::query()->first();
    expect($row)->not->toBeNull();
    expect($row->status)->toBe('pending');
    expect($row->event_type)->toBe('payment.received');
    expect($row->aggregate_id)->toBe($event->aggregateId());
    expect($row->retry_count)->toBe(0);
    expect($row->payload['event_class'])->toBe(PaymentReceived::class);
    expect($row->payload['data'])->toEqualCanonicalizing($event->payload());

    Event::assertNotDispatched(PaymentReceived::class);
});

it('dispatches event through laravel dispatcher when reliable=false and does not touch outbox', function (): void {
    Event::fake();

    $event = makePaymentReceived();
    outboxPublisher()->publish($event, reliable: false);

    expect(OutboxMessageModel::query()->count())->toBe(0);

    Event::assertDispatched(PaymentReceived::class, 1);
    Event::assertDispatched('payment.received');
});

it('defaults to fire-and-forget dispatch when reliable flag omitted', function (): void {
    Event::fake();

    $event = makePaymentReceived();
    outboxPublisher()->publish($event);

    expect(OutboxMessageModel::query()->count())->toBe(0);
    Event::assertDispatched(PaymentReceived::class, 1);
});
