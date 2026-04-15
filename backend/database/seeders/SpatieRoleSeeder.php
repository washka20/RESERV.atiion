<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Создаёт Spatie-роли для web guard.
 *
 * Должен запускаться до AdminUserSeeder — listener синхронизации
 * (SyncSpatieRoleOnUserRoleAssigned) требует чтобы Spatie-роль уже существовала.
 */
final class SpatieRoleSeeder extends Seeder
{
    private const ROLES = ['admin', 'manager', 'customer'];

    public function run(): void
    {
        foreach (self::ROLES as $name) {
            SpatieRole::findOrCreate($name, 'web');
        }
    }
}
