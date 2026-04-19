<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\DTO;

use App\Modules\Identity\Domain\Entity\Organization;

/**
 * DTO проекции Organization для Application/API слоёв.
 * Создаётся из domain entity через fromEntity (read-only snapshot).
 */
final readonly class OrganizationDTO
{
    /**
     * @param  array<string, string>  $name  локаль => перевод
     * @param  array<string, string>  $description  локаль => перевод
     */
    public function __construct(
        public string $id,
        public string $slug,
        public array $name,
        public array $description,
        public string $type,
        public ?string $logoUrl,
        public string $city,
        public ?string $district,
        public string $phone,
        public string $email,
        public bool $verified,
        public string $cancellationPolicy,
        public float $rating,
        public int $reviewsCount,
        public bool $archived,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Organization $org): self
    {
        return new self(
            id: $org->id->toString(),
            slug: $org->slug->toString(),
            name: $org->nameTranslations(),
            description: $org->descriptionTranslations(),
            type: $org->type->value,
            logoUrl: $org->logoUrl(),
            city: $org->city(),
            district: $org->district(),
            phone: $org->phone(),
            email: $org->email(),
            verified: $org->isVerified(),
            cancellationPolicy: $org->cancellationPolicy()->value,
            rating: $org->rating(),
            reviewsCount: $org->reviewsCount(),
            archived: $org->isArchived(),
            createdAt: $org->createdAt->format(DATE_ATOM),
            updatedAt: $org->updatedAt()->format(DATE_ATOM),
        );
    }
}
