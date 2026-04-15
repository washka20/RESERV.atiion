<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Resource\UserResource\Pages\CreateUser;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, SpatieRoleSeeder::class]);
});

it('CreateUser through Livewire creates user via RegisterUserCommand', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    actingAs($admin, 'web');

    Livewire::test(CreateUser::class)
        ->set('data.email', 'new@example.com')
        ->set('data.password', 'secret1234')
        ->set('data.first_name', 'Ivan')
        ->set('data.last_name', 'Petrov')
        ->call('create')
        ->assertHasNoFormErrors();

    expect(UserModel::where('email', 'new@example.com')->exists())->toBeTrue();
});
