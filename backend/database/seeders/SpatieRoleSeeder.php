<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Создаёт Spatie-роли для web guard (platform-level RBAC).
 *
 * Должен запускаться до AdminUserSeeder — listener синхронизации
 * (SyncSpatieRoleOnUserRoleAssigned) требует чтобы Spatie-роль уже существовала.
 *
 * Platform permissions (organizations.*, memberships.*) — отдельная ось от
 * organization-level MembershipRole permissions. Используются в Filament admin UI.
 */
final class SpatieRoleSeeder extends Seeder
{
    private const ROLES = ['admin', 'manager', 'customer'];

    /**
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'organizations.view',
            'organizations.verify',
            'organizations.archive',
            'memberships.view',
        ],
        'manager' => [
            'organizations.view',
            'memberships.view',
        ],
        'customer' => [],
    ];

    public function run(): void
    {
        // Сбросить Spatie permission cache В НАЧАЛЕ. Критично под --parallel:
        // предыдущий тест seed'ил permissions → cache их запомнил. Текущий тест
        // сделал RefreshDatabase (pure empty DB) → вызывает этот seeder снова.
        // findOrCreate проверяет cache, видит "permission существует", возвращает stale entry,
        // НЕ пишет в пустую БД. Следующий syncPermissions идёт в БД → permission не найден → throw.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (array_unique(array_merge(...array_values(self::ROLE_PERMISSIONS))) as $permission) {
            SpatiePermission::findOrCreate($permission, 'web');
        }

        // И ещё раз — после создания permissions, чтобы syncPermissions ниже увидел новые.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $name) {
            $role = SpatieRole::findOrCreate($name, 'web');
            $role->syncPermissions(self::ROLE_PERMISSIONS[$name] ?? []);
        }
    }
}
