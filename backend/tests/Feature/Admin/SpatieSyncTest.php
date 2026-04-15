<?php

declare(strict_types=1);

use App\Modules\Identity\Application\Command\AssignRole\AssignRoleCommand;
use App\Modules\Identity\Domain\ValueObject\RoleName;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SpatieRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, SpatieRoleSeeder::class]);
});

it('AssignRoleCommand syncs spatie role via listener', function (): void {
    $user = UserModel::factory()->create();

    app(CommandBusInterface::class)->dispatch(
        new AssignRoleCommand(
            userId: (string) $user->id,
            roleName: RoleName::Admin,
        ),
    );

    expect($user->fresh()->hasRole('admin'))->toBeTrue();
});
