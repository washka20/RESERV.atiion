<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\ListUsers;

use App\Modules\Identity\Application\DTO\UserDTO;
use Illuminate\Support\Facades\DB;

final readonly class ListUsersHandler
{
    /**
     * @return array{items: list<UserDTO>, total: int, page: int, per_page: int}
     */
    public function handle(ListUsersQuery $query): array
    {
        $total = (int) DB::table('users')->count();
        $offset = ($query->page - 1) * $query->perPage;

        $rows = DB::table('users')
            ->orderBy('created_at', 'desc')
            ->offset($offset)
            ->limit($query->perPage)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $roleNames = DB::table('role_user')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->where('role_user.user_id', $row->id)
                ->pluck('roles.name')
                ->toArray();

            $items[] = new UserDTO(
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

        return [
            'items' => $items,
            'total' => $total,
            'page' => $query->page,
            'per_page' => $query->perPage,
        ];
    }
}
