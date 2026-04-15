<?php

declare(strict_types=1);

use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Resource\UserResource;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(SpatieRoleSeeder::class);
});

it('admin can edit, delete, create users', function (): void {
    $admin = UserModel::factory()->create();
    $admin->assignRole('admin');
    $target = UserModel::factory()->create();

    actingAs($admin, 'web');

    expect(UserResource::canEdit($target))->toBeTrue();
    expect(UserResource::canDelete($target))->toBeTrue();
    expect(UserResource::canCreate())->toBeTrue();
});

it('manager can edit but cannot delete or create', function (): void {
    $manager = UserModel::factory()->create();
    $manager->assignRole('manager');
    $target = UserModel::factory()->create();

    actingAs($manager, 'web');

    expect(UserResource::canEdit($target))->toBeTrue();
    expect(UserResource::canDelete($target))->toBeFalse();
    expect(UserResource::canCreate())->toBeFalse();
});
