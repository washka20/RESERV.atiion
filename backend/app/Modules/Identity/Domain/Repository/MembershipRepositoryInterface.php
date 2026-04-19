<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Repository;

use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;

/**
 * Репозиторий Membership aggregate.
 *
 * Уникальность пары (user_id, organization_id) enforced на уровне БД (unique index).
 * countOwnersInOrganization используется RevokeMembershipHandler для enforcement
 * инварианта "last owner cannot be revoked".
 */
interface MembershipRepositoryInterface
{
    /**
     * Сохраняет membership (insert или update по id).
     */
    public function save(Membership $membership): void;

    /**
     * Ищет membership по уникальному id.
     */
    public function findById(MembershipId $id): ?Membership;

    /**
     * Возвращает membership пары (user, organization) — один или ноль,
     * т.к. комбинация уникальна.
     */
    public function findByPair(UserId $userId, OrganizationId $organizationId): ?Membership;

    /**
     * Возвращает все memberships указанного user'а (во всех organizations).
     *
     * @return list<Membership>
     */
    public function findByUserId(UserId $userId): array;

    /**
     * Возвращает все memberships указанной organization (вся команда).
     *
     * @return list<Membership>
     */
    public function findByOrganizationId(OrganizationId $organizationId): array;

    /**
     * Подсчитывает число membership'ов с ролью OWNER в указанной organization —
     * для защиты инварианта "last owner cannot be revoked/demoted".
     */
    public function countOwnersInOrganization(OrganizationId $organizationId): int;

    /**
     * Удаляет membership по id. Идемпотентно: если id не существует — no-op.
     */
    public function delete(MembershipId $id): void;
}
