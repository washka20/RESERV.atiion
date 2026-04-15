<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Resource\UserResource\Pages\EditUser;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, SpatieRoleSeeder::class]);
});

it('EditUser through Livewire updates user via UpdateUserCommand', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    $target = UserModel::factory()->create([
        'email' => 'old@example.com',
        'first_name' => 'Old',
    ]);
    actingAs($admin, 'web');

    Livewire::test(EditUser::class, ['record' => $target->getKey()])
        ->set('data.email', 'updated@example.com')
        ->set('data.first_name', 'New')
        ->set('data.last_name', $target->last_name)
        ->call('save')
        ->assertHasNoFormErrors();

    $fresh = $target->fresh();
    expect($fresh->email)->toBe('updated@example.com');
    expect($fresh->first_name)->toBe('New');
});
