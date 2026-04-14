<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'admin@example.com';

        $exists = DB::table('users')->where('email', $email)->exists();

        if (! $exists) {
            DB::table('users')->insert([
                'id' => (string) Str::uuid(),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => $email,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $userId = DB::table('users')->where('email', $email)->value('id');

        DB::table('role_user')->updateOrInsert(
            ['user_id' => $userId, 'role_id' => RoleSeeder::ADMIN_ID],
        );
    }
}
