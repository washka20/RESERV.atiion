<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name'=>'show']);
        Permission::create(['name'=>'add categories']);
        Permission::create(['name'=>'add subcategories']);
        Permission::create(['name'=>'add objects']);
        Permission::create(['name'=>'edit categories']);
        Permission::create(['name'=>'edit subcategories']);
        Permission::create(['name'=>'edit objects']);
        Permission::create(['name'=>'delete categories']);
        Permission::create(['name'=>'delete subcategories']);
        Permission::create(['name'=>'delete objects']);
    }
}
