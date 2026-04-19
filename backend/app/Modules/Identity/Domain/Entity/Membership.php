<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\Entity;

use App\Modules\Identity\Domain\Event\MembershipGranted;
use App\Modules\Identity\Domain\Event\MembershipRoleChanged;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

/**
 * Членство user'а в organization — aggregate root Identity BC.
 *
 * Пара (userId, organizationId) уникальна: повторный grant должен идти через
 * changeRole existing membership, не создавать новый. Inviter отмечается
 * в invitedBy для audit trail (null для self-created owner'а).
 *
 * Последнего OWNER organization'а нельзя revoke/demote — проверка уровня
 * application layer (RevokeMembershipHandler через countOwnersInOrganization).
 */
final class Membership extends AggregateRoot
{
    private function __construct(
        public readonly MembershipId $id,
        public readonly UserId $userId,
        public readonly OrganizationId $organizationId,
        private MembershipRole $role,
        public readonly ?UserId $invitedBy,
        private ?DateTimeImmutable $acceptedAt,
        public readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {}

    /**
     * Создаёт новое членство с auto-accept (acceptedAt = now) и записывает
     * MembershipGranted event. Используется для direct grant — self-added owner
     * при создании organization или invite, принятый автоматически.
     */
    public static function grant(
        MembershipId $id,
        UserId $userId,
        OrganizationId $organizationId,
        MembershipRole $role,
        ?UserId $invitedBy = null,
    ): self {
        $now = new DateTimeImmutable;
        $membership = new self(
            id: $id,
            userId: $userId,
            organizationId: $organizationId,
            role: $role,
            invitedBy: $invitedBy,
            acceptedAt: $now,
            createdAt: $now,
            updatedAt: $now,
        );
        $membership->recordEvent(new MembershipGranted($id, $userId, $organizationId, $role, $now));

        return $membership;
    }

    /**
     * Восстанавливает Membership из persistence без записи domain events.
     * Используется mapper'ом репозитория.
     */
    public static function reconstitute(
        MembershipId $id,
        UserId $userId,
        OrganizationId $organizationId,
        MembershipRole $role,
        ?UserId $invitedBy,
        ?DateTimeImmutable $acceptedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            $id,
            $userId,
            $organizationId,
            $role,
            $invitedBy,
            $acceptedAt,
            $createdAt,
            $updatedAt,
        );
    }

    /**
     * Меняет роль на указанную. Идемпотентно: установка той же роли не эмитит
     * event и не меняет updatedAt. Invariant "last owner not demoted" проверяется
     * на уровне application handler (знает количество owners в organization).
     */
    public function changeRole(MembershipRole $newRole): void
    {
        if ($this->role === $newRole) {
            return;
        }

        $oldRole = $this->role;
        $this->role = $newRole;
        $this->updatedAt = new DateTimeImmutable;
        $this->recordEvent(new MembershipRoleChanged($this->id, $oldRole, $newRole, $this->updatedAt));
    }

    public function role(): MembershipRole
    {
        return $this->role;
    }

    public function acceptedAt(): ?DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Удобный предикат — прошёл ли invite стадию acceptance.
     */
    public function isAccepted(): bool
    {
        return $this->acceptedAt !== null;
    }
}
