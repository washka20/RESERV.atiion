<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Event;

use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Услуга деактивирована (скрыта из каталога).
 */
final readonly class ServiceDeactivated implements DomainEvent
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
        return 'catalog.service.deactivated';
    }

    public function payload(): array
    {
        return [
            'service_id' => $this->serviceId->toString(),
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
