<?php

declare(strict_types=1);

namespace Database\Seeders\Identity;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Сидер memberships: связываем sample-пользователей с demo organizations.
 *
 * Порядок: зависит от AdminUserSeeder + TestUserSeeder + OrganizationsSeeder.
 *
 * - admin@example.com  → Platform Admin (owner)
 * - test@example.com   → Salon Savvin (owner), Loft 23 (staff)
 */
final class MembershipsSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = DB::table('users')->where('email', 'admin@example.com')->value('id');
        $testUserId = DB::table('users')->where('email', 'test@example.com')->value('id');

        if ($adminUserId === null || $testUserId === null) {
            return;
        }

        DB::table('memberships')->updateOrInsert(
            [
                'user_id' => $adminUserId,
                'organization_id' => OrganizationsSeeder::PLATFORM_ADMIN_ORG_ID,
            ],
            [
                'id' => '00000000-0000-0000-0000-00000000a001',
                'role' => 'owner',
                'invited_by' => null,
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('memberships')->updateOrInsert(
            [
                'user_id' => $testUserId,
                'organization_id' => OrganizationsSeeder::SALON_SAVVIN_ID,
            ],
            [
                'id' => '00000000-0000-0000-0000-00000000a010',
                'role' => 'owner',
                'invited_by' => null,
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        DB::table('memberships')->updateOrInsert(
            [
                'user_id' => $testUserId,
                'organization_id' => OrganizationsSeeder::LOFT_23_ID,
            ],
            [
                'id' => '00000000-0000-0000-0000-00000000a011',
                'role' => 'staff',
                'invited_by' => $adminUserId,
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
