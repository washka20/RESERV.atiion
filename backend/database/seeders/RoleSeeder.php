<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class RoleSeeder extends Seeder
{
    public const ADMIN_ID = '00000000-0000-4000-8000-000000000001';

    public const MANAGER_ID = '00000000-0000-4000-8000-000000000002';

    public const USER_ID = '00000000-0000-4000-8000-000000000003';

    public function run(): void
    {
        $roles = [
            ['id' => self::ADMIN_ID, 'name' => 'admin'],
            ['id' => self::MANAGER_ID, 'name' => 'manager'],
            ['id' => self::USER_ID, 'name' => 'user'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                ['id' => $role['id']],
            );
        }
    }
}
