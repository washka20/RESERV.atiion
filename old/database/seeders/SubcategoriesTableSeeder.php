<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubcategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subcategories')->insert([
            ['category_id' => 1, 'name' => 'Теннис', 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => 1, 'name' => 'Футбол', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
