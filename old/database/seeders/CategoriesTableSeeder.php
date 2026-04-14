<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            ['name' => 'Спорт', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Машины', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
