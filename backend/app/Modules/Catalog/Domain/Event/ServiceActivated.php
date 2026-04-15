<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Event;

use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Услуга активирована (возвращена в каталог).
 */
final readonly class ServiceActivated implements DomainEvent
{
    public function __construct(
        private ServiceId $serviceId,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function serviceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function aggregateId(): string
    {
        return $this->serviceId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'catalog.service.activated';
    }

    public function payload(): array
    {
        return [
            'service_id' => $this->serviceId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
