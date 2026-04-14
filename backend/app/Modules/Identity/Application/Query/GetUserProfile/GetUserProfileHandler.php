<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\GetUserProfile;

use App\Modules\Identity\Application\DTO\UserDTO;
use Illuminate\Support\Facades\DB;

final readonly class GetUserProfileHandler
{
    public function handle(GetUserProfileQuery $query): ?UserDTO
    {
        $row = DB::table('users')
            ->where('id', $query->userId)
            ->first();

        if ($row === null) {
            return null;
        }

        $roleNames = DB::table('role_user')
            ->join('roles', 'role_user.role_id', '=', 'roles.id')
            ->where('role_user.user_id', $query->userId)
            ->pluck('roles.name')
            ->toArray();

        return new UserDTO(
            id: (string) $row->id,
            email: (string) $row->email,
            firstName: (string) $row->first_name,
            lastName: (string) $row->last_name,
            middleName: $row->middle_name !== null ? (string) $row->middle_name : null,
            roles: array_values(array_map('strval', $roleNames)),
            emailVerifiedAt: $row->email_verified_at !== null ? (string) $row->email_verified_at : null,
            createdAt: (string) $row->created_at,
        );
    }
}
