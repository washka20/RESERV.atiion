<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Resource\UserResource;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SpatieRoleSeeder::class);
});

it('admin can access users list', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');

    actingAs($admin, 'web')
        ->get(UserResource::getUrl('index'))
        ->assertOk();
});

it('manager can access users list', function (): void {
    $manager = UserModel::factory()->create();
    $manager->assignRole('manager');

    actingAs($manager, 'web')
        ->get(UserResource::getUrl('index'))
        ->assertOk();
});

it('customer is forbidden from admin panel', function (): void {
    $customer = UserModel::factory()->create();
    $customer->assignRole('customer');

    actingAs($customer, 'web')
        ->get('/admin')
        ->assertForbidden();
});

it('guest is redirected to login', function (): void {
    get('/admin')->assertRedirect('/admin/login');
});
