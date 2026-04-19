<?php

declare(strict_types=1);

namespace App\Shared\Application\Identity;

/**
 * Межмодульный Identity lookup: проверка принадлежности user'а к organization.
 *
 * Используется в других BC (Payment, Booking) для авторизации действий, требующих
 * членства с определённой ролью (например, OWNER для управления payout settings).
 * Реализуется в Identity BC и биндится через Provider.
 */
interface MembershipLookupInterface
{
    /**
     * Возвращает true, если user имеет membership с ролью OWNER в указанной organization.
     */
    public function isOwner(string $userId, string $organizationId): bool;
}
