<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain;

use App\Shared\Domain\AggregateRoot;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FakeEvent implements DomainEvent
{
    public function __construct(private readonly string $aggregateId) {}

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return new DateTimeImmutable;
    }

    public function eventName(): string
    {
        return 'fake.event';
    }

    public function payload(): array
    {
        return ['aggregate_id' => $this->aggregateId];
    }

    public static function fromPayload(array $payload): self
    {
        return new self((string) $payload['aggregate_id']);
    }
}

final class FakeAggregate extends AggregateRoot
{
    public function emit(DomainEvent $event): void
    {
        $this->recordEvent($event);
    }
}

final class AggregateRootTest extends TestCase
{
    public function test_starts_with_no_events(): void
    {
        $ar = new FakeAggregate;
        $this->assertSame([], $ar->pullDomainEvents());
    }

    public function test_records_and_pulls_events_in_order(): void
    {
        $ar = new FakeAggregate;
        $ar->emit(new FakeEvent('a'));
        $ar->emit(new FakeEvent('b'));

        $events = $ar->pullDomainEvents();
        $this->assertCount(2, $events);
        $this->assertSame('a', $events[0]->aggregateId());
        $this->assertSame('b', $events[1]->aggregateId());
    }

    public function test_pull_clears_internal_buffer(): void
    {
        $ar = new FakeAggregate;
        $ar->emit(new FakeEvent('a'));

        $ar->pullDomainEvents();
        $this->assertSame([], $ar->pullDomainEvents(), 'second pull must return empty');
    }
}
