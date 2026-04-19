<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Resource;

use App\Modules\Identity\Application\DTO\MembershipWithOrgDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализует MembershipWithOrgDTO — элемент списка /me/memberships.
 *
 * @property MembershipWithOrgDTO $resource
 */
final class MembershipWithOrgResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MembershipWithOrgDTO $dto */
        $dto = $this->resource;

        return [
            'membership_id' => $dto->membershipId,
            'organization_id' => $dto->organizationId,
            'organization_slug' => $dto->organizationSlug,
            'role' => $dto->role,
        ];
    }
}
