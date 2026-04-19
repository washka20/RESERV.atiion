<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Resource;

use App\Modules\Identity\Application\DTO\MembershipDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализует MembershipDTO для API (invite / change-role responses).
 *
 * @property MembershipDTO $resource
 */
final class MembershipResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MembershipDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'user_id' => $dto->userId,
            'organization_id' => $dto->organizationId,
            'role' => $dto->role,
            'invited_by' => $dto->invitedBy,
            'accepted_at' => $dto->acceptedAt,
            'created_at' => $dto->createdAt,
        ];
    }
}
