<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Service;

use App\Modules\Identity\Application\DTO\MembershipWithOrgDTO;

/**
 * Лёгкий lookup-contract для получения memberships пользователя с embedded
 * org slug. Отдельный интерфейс нужен, чтобы AuthService зависел от абстракции
 * (можно подменить в юнит-тестах без Laravel/DB) — основной реализацией
 * является ListUserMembershipsHandler.
 */
interface UserMembershipsLookupInterface
{
    /**
     * @return list<MembershipWithOrgDTO>
     */
    public function forUser(string $userId): array;
}
