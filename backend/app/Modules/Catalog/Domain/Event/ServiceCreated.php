<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Event;

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Услуга создана.
 */
final readonly class ServiceCreated implements DomainEvent
{
    public function __construct(
        private ServiceId $serviceId,
        private CategoryId $categoryId,
        private ServiceType $type,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function serviceId(): ServiceId
    {
        return $this->serviceId;
    }

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function type(): ServiceType
    {
        return $this->type;
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
        return 'catalog.service.created';
    }

    public function payload(): array
    {
        return [
            'service_id' => $this->serviceId->toString(),
            'category_id' => $this->categoryId->toString(),
            'type' => $this->type->value,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }
}
