<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Event;

use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Shared\Domain\DomainEvent;
use DateTimeImmutable;

/**
 * Категория создана.
 */
final readonly class CategoryCreated implements DomainEvent
{
    public function __construct(
        private CategoryId $categoryId,
        private string $slug,
        private DateTimeImmutable $occurredAt,
    ) {}

    public function categoryId(): CategoryId
    {
        return $this->categoryId;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function aggregateId(): string
    {
        return $this->categoryId->toString();
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function eventName(): string
    {
        return 'catalog.category.created';
    }

    public function payload(): array
    {
        return [
            'category_id' => $this->categoryId->toString(),
            'slug' => $this->slug,
            'occurred_at' => $this->occurredAt->format(DATE_ATOM),
        ];
    }

    public static function fromPayload(array $payload): self
    {
        return new self(
            new CategoryId((string) $payload['category_id']),
            (string) $payload['slug'],
            new DateTimeImmutable((string) $payload['occurred_at']),
        );
    }
}
