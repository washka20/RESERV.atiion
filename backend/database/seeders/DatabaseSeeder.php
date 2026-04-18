<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SpatieRoleSeeder::class,
            AdminUserSeeder::class,
            TestUserSeeder::class,
            CategoriesSeeder::class,
            SubcategoriesSeeder::class,
            ServicesSeeder::class,
            Booking\TimeSlotsSeeder::class,
            Booking\BookingsSeeder::class,
        ]);
    }
}
