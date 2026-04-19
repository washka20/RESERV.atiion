<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Resource;

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализует OrganizationDTO в snake_case JSON для публичного API.
 *
 * @property OrganizationDTO $resource
 */
final class OrganizationResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var OrganizationDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'slug' => $dto->slug,
            'name' => $dto->name,
            'description' => $dto->description,
            'type' => $dto->type,
            'logo_url' => $dto->logoUrl,
            'city' => $dto->city,
            'district' => $dto->district,
            'phone' => $dto->phone,
            'email' => $dto->email,
            'verified' => $dto->verified,
            'cancellation_policy' => $dto->cancellationPolicy,
            'rating' => $dto->rating,
            'reviews_count' => $dto->reviewsCount,
            'archived' => $dto->archived,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
