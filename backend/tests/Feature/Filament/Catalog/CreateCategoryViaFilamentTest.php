<?php

declare(strict_types=1);

use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Interface\Filament\Resource\CategoryResource\Pages\CreateCategory;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, SpatieRoleSeeder::class]);
});

it('admin creates category via filament form', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    Livewire::test(CreateCategory::class)
        ->set('data.name', 'Новая категория')
        ->set('data.slug', 'new-category')
        ->set('data.sort_order', 50)
        ->call('create')
        ->assertHasNoFormErrors();

    $category = CategoryModel::query()->where('slug', 'new-category')->first();
    expect($category)->not->toBeNull();
    expect($category->name)->toBe('Новая категория');
    expect($category->sort_order)->toBe(50);
});
