<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            ['category_id' => 1, 'subcategory_id' => 1, 'name' => 'Теннисный мяч', 'description' => 'Профессиональный теннисный мяч', 'price' => 100.00, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => 2, 'subcategory_id' => null, 'name' => 'Спортивный автомобиль', 'description' => 'Высокоскоростной спортивный автомобиль', 'price' => 200000.00, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => 1, 'subcategory_id' => 1, 'name' => 'МЯЧИК', 'description' => 'СПОРТИК МЯЧИК', 'price' => 666.00, 'created_at' => now(), 'updated_at' => now()],
            ['category_id' => 2, 'subcategory_id' => null, 'name' => 'БИБИКА', 'description' => 'БИБИКИ БРРРР БРРРР', 'price' => 696969.00, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
