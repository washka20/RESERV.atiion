<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

it('seeds Spatie roles с platform permissions', function (): void {
    expect(SpatieRole::where('name', 'admin')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(SpatieRole::where('name', 'manager')->where('guard_name', 'web')->exists())->toBeTrue();
    expect(SpatieRole::where('name', 'customer')->where('guard_name', 'web')->exists())->toBeTrue();

    expect(SpatiePermission::where('name', 'organizations.view')->exists())->toBeTrue();
    expect(SpatiePermission::where('name', 'organizations.archive')->exists())->toBeTrue();
    expect(SpatiePermission::where('name', 'memberships.view')->exists())->toBeTrue();
});

it('AdminUserSeeder создал admin@example.com', function (): void {
    expect(DB::table('users')->where('email', 'admin@example.com')->exists())->toBeTrue();
    expect(DB::table('users')->where('email', 'admin@example.com')->value('email_verified_at'))->not->toBeNull();
});

it('все таблицы имеют хотя бы строку данных после db:seed', function (): void {
    $tables = ['users', 'organizations', 'memberships', 'categories', 'subcategories', 'services', 'time_slots', 'bookings'];
    foreach ($tables as $table) {
        expect(DB::table($table)->count())->toBeGreaterThan(0, "Таблица {$table} пустая — seeder не отработал");
    }
});
