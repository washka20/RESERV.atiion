<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use phpDocumentor\Reflection\Types\Nullable;
use Spatie\Permission\Models\Role;

class CreateSuperAdminSeeder extends Seeder
{


    public function run(): void
    {
        $superAdmin = User::create([
            'email' => 'admin@gmail.com',
            'last_name' => 'Super',
            'first_name' => 'Admin',
            'middle_name' => null,
            'password' => Hash::make('1234567890'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Role::create([
            'name' => 'super-admin',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $superAdmin->assignRole('super-admin');
    }
}
