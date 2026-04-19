<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Resource;

use App\Modules\Identity\Application\DTO\MemberListItemDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализует MemberListItemDTO — элемент списка members в GET /members.
 *
 * @property MemberListItemDTO $resource
 */
final class MemberListItemResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var MemberListItemDTO $dto */
        $dto = $this->resource;

        return [
            'membership_id' => $dto->membershipId,
            'role' => $dto->role,
            'accepted_at' => $dto->acceptedAt,
            'joined_at' => $dto->joinedAt,
            'user' => [
                'id' => $dto->userId,
                'email' => $dto->userEmail,
                'first_name' => $dto->userFirstName,
                'last_name' => $dto->userLastName,
            ],
        ];
    }
}
