<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Resource;

use App\Modules\Identity\Application\DTO\UserDTO;

final class UserResource
{
    /**
     * @return array<string, mixed>
     */
    public static function fromDTO(UserDTO $dto): array
    {
        return [
            'id' => $dto->id,
            'email' => $dto->email,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'middle_name' => $dto->middleName,
            'roles' => $dto->roles,
            'email_verified_at' => $dto->emailVerifiedAt,
            'created_at' => $dto->createdAt,
        ];
    }
}
