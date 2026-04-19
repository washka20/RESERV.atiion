<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

/**
 * Роль user'а внутри Organization — organization-level RBAC.
 *
 * Не путать с Spatie platform roles (admin/manager/user) — это независимая
 * система permissions scoped к конкретной organization.
 *
 * - OWNER — основатель, полный контроль; в organization должен быть как минимум 1
 * - ADMIN — полномочный помощник: services, bookings, team (без archive org)
 * - STAFF — оперативные действия: confirm/cancel bookings, edit own services
 * - VIEWER — только чтение
 *
 * Permissions matrix в `self::PERMISSIONS` — single source of truth.
 * Middleware `MembershipGuardMiddleware` проверяет через `can(string $permission)`.
 */
enum MembershipRole: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case STAFF = 'staff';
    case VIEWER = 'viewer';

    /**
     * @var array<string, list<self>>
     */
    private const PERMISSIONS = [
        'services.create' => [self::OWNER, self::ADMIN],
        'services.edit' => [self::OWNER, self::ADMIN, self::STAFF],
        'services.delete' => [self::OWNER, self::ADMIN],
        'bookings.confirm' => [self::OWNER, self::ADMIN, self::STAFF],
        'bookings.cancel' => [self::OWNER, self::ADMIN, self::STAFF],
        'bookings.view' => [self::OWNER, self::ADMIN, self::STAFF, self::VIEWER],
        'payouts.view' => [self::OWNER, self::ADMIN],
        'team.view' => [self::OWNER, self::ADMIN, self::STAFF],
        'team.manage' => [self::OWNER],
        'settings.view' => [self::OWNER, self::ADMIN, self::STAFF],
        'settings.manage' => [self::OWNER, self::ADMIN],
        'organization.archive' => [self::OWNER],
    ];

    public function can(string $permission): bool
    {
        $allowed = self::PERMISSIONS[$permission] ?? null;
        if ($allowed === null) {
            return false;
        }

        return in_array($this, $allowed, true);
    }

    /**
     * Возвращает список всех permission-ключей, поддерживаемых системой.
     * Используется для валидации / документации / admin UI.
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        return array_keys(self::PERMISSIONS);
    }
}
