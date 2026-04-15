<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Modules\Catalog\Interface\Filament\Resource\ServiceResource\Pages\CreateService;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, SpatieRoleSeeder::class, CategoriesSeeder::class]);
});

it('admin creates time_slot service via filament form', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    $categoryId = DB::table('categories')->value('id');

    Livewire::test(CreateService::class)
        ->set('data.name', 'Premium Haircut')
        ->set('data.description', 'Professional premium haircut experience')
        ->set('data.category_id', $categoryId)
        ->set('data.type', 'time_slot')
        ->set('data.duration_minutes', 60)
        ->set('data.price_amount', 250000)
        ->set('data.price_currency', 'RUB')
        ->call('create')
        ->assertHasNoFormErrors();

    $service = ServiceModel::query()->where('name', 'Premium Haircut')->first();
    expect($service)->not->toBeNull();
    expect($service->type)->toBe('time_slot');
    expect($service->duration_minutes)->toBe(60);
    expect($service->price_amount)->toBe(250000);
    expect($service->price_currency)->toBe('RUB');
    expect((bool) $service->is_active)->toBeTrue();
});

it('admin creates quantity service via filament form', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    $categoryId = DB::table('categories')->value('id');

    Livewire::test(CreateService::class)
        ->set('data.name', 'Hotel Room')
        ->set('data.description', 'Luxury hotel room with sea view')
        ->set('data.category_id', $categoryId)
        ->set('data.type', 'quantity')
        ->set('data.total_quantity', 20)
        ->set('data.price_amount', 500000)
        ->set('data.price_currency', 'RUB')
        ->call('create')
        ->assertHasNoFormErrors();

    $service = ServiceModel::query()->where('name', 'Hotel Room')->first();
    expect($service)->not->toBeNull();
    expect($service->type)->toBe('quantity');
    expect($service->total_quantity)->toBe(20);
});
