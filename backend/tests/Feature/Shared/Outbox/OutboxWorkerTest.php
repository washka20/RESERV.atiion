<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Shared\Application\Outbox\OutboxPublisherInterface;
use App\Shared\Infrastructure\Outbox\DomainEventSerializer;
use App\Shared\Infrastructure\Outbox\OutboxMessageModel;
use App\Shared\Infrastructure\Outbox\OutboxWorker;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Log\NullLogger;

uses(RefreshDatabase::class);

function workerMakeEvent(): PaymentReceived
{
    return new PaymentReceived(
        PaymentId::generate(),
        BookingId::generate(),
        Money::fromCents(100_000),
        Money::fromCents(10_000),
        Money::fromCents(90_000),
        'prov-ref',
        new DateTimeImmutable('2026-04-19T10:00:00+00:00'),
    );
}

function seedPendingEvent(): OutboxMessageModel
{
    /** @var OutboxPublisherInterface $publisher */
    $publisher = app(OutboxPublisherInterface::class);
    $publisher->publish(workerMakeEvent(), reliable: true);

    /** @var OutboxMessageModel $row */
    $row = OutboxMessageModel::query()->first();

    return $row;
}

it('marks pending messages as published after successful dispatch', function (): void {
    seedPendingEvent();

    /** @var OutboxWorker $worker */
    $worker = app(OutboxWorker::class);
    $processed = $worker->runOnce();

    expect($processed)->toBe(1);

    $row = OutboxMessageModel::query()->first();
    expect($row->status)->toBe('published');
    expect($row->published_at)->not->toBeNull();
    expect($row->last_error)->toBeNull();
});

function failingDispatcher(string $message): Dispatcher
{
    return new class($message) implements Dispatcher
    {
        public function __construct(private readonly string $message) {}

        public function listen($events, $listener = null): void {}

        public function hasListeners($eventName): bool
        {
            return false;
        }

        public function subscribe($subscriber): void {}

        public function until($event, $payload = []): mixed
        {
            return null;
        }

        public function dispatch($event, $payload = [], $halt = false): mixed
        {
            throw new RuntimeException($this->message);
        }

        public function push($event, $payload = []): void {}

        public function flush($event): void {}

        public function forget($event): void {}

        public function forgetPushed(): void {}
    };
}

function makeWorker(Dispatcher $dispatcher): OutboxWorker
{
    return new OutboxWorker(
        new DomainEventSerializer,
        $dispatcher,
        new NullLogger,
        app('config'),
    );
}

it('bumps retry_count and schedules next_attempt_at when dispatch throws', function (): void {
    seedPendingEvent();

    $worker = makeWorker(failingDispatcher('boom'));
    $worker->runOnce();

    $row = OutboxMessageModel::query()->first();
    expect($row->status)->toBe('pending');
    expect($row->retry_count)->toBe(1);
    expect($row->next_attempt_at)->not->toBeNull();
    expect($row->last_error)->toContain('boom');
});

it('transitions to failed when retry_count reaches max_retries', function (): void {
    config()->set('payments.outbox.max_retries', 2);

    $row = seedPendingEvent();
    $row->retry_count = 1;
    $row->save();

    $worker = makeWorker(failingDispatcher('final boom'));
    $worker->runOnce();

    $fresh = OutboxMessageModel::query()->first();
    expect($fresh->status)->toBe('failed');
    expect($fresh->retry_count)->toBe(2);
    expect($fresh->failed_at)->not->toBeNull();
});

it('does not pick up messages with next_attempt_at in the future', function (): void {
    $row = seedPendingEvent();
    $row->next_attempt_at = (new DateTimeImmutable)->modify('+1 hour');
    $row->save();

    /** @var OutboxWorker $worker */
    $worker = app(OutboxWorker::class);
    $processed = $worker->runOnce();

    expect($processed)->toBe(0);

    $fresh = OutboxMessageModel::query()->first();
    expect($fresh->status)->toBe('pending');
});
