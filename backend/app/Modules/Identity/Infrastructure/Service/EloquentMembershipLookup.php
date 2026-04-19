<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Service;

use App\Modules\Identity\Infrastructure\Persistence\Model\MembershipModel;
use App\Shared\Application\Identity\MembershipLookupInterface;

/**
 * Eloquent-реализация {@see MembershipLookupInterface}.
 *
 * Читает таблицу memberships напрямую — простая булева проверка роли OWNER,
 * не требует доменной сборки агрегата.
 */
final class EloquentMembershipLookup implements MembershipLookupInterface
{
    public function isOwner(string $userId, string $organizationId): bool
    {
        return MembershipModel::query()
            ->where('user_id', $userId)
            ->where('organization_id', $organizationId)
            ->where('role', 'owner')
            ->exists();
    }
}
