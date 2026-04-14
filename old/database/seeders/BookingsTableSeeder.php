<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bookings')->insert([
            ['user_id' => 1, 'product_id' => 1, 'booking_time' => now()->addDays(1), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 2, 'product_id' => 2, 'booking_time' => now()->addDays(1), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 1, 'product_id' => 4, 'booking_time' => now()->addDays(1), 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 2, 'product_id' => 3, 'booking_time' => now()->addDays(1), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
