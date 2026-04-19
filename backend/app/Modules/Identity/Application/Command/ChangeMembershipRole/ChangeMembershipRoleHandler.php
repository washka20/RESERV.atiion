<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\ChangeMembershipRole;

use App\Modules\Identity\Application\DTO\MembershipDTO;
use App\Modules\Identity\Domain\Exception\LastOwnerCannotBeRevokedException;
use App\Modules\Identity\Domain\Exception\MembershipNotFoundException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Меняет роль existing membership. Permission: team.manage (только owner).
 *
 * Защита инварианта: нельзя demote последнего owner'а. Идемпотентно:
 * смена на ту же роль — no-op (entity сам проверяет).
 */
final readonly class ChangeMembershipRoleHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(ChangeMembershipRoleCommand $cmd): MembershipDTO
    {
        $newRole = MembershipRole::from($cmd->newRole);

        $slug = new OrganizationSlug($cmd->organizationSlug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        $actorId = new UserId($cmd->actorUserId);
        $actorMembership = $this->memberships->findByPair($actorId, $organization->id);
        if ($actorMembership === null || ! $actorMembership->role()->can('team.manage')) {
            throw new RuntimeException('Forbidden: only organization owner can change member roles');
        }

        $targetId = new MembershipId($cmd->targetMembershipId);
        $target = $this->memberships->findById($targetId);
        if ($target === null) {
            throw MembershipNotFoundException::byId($targetId);
        }

        if (! $target->organizationId->equals($organization->id)) {
            throw MembershipNotFoundException::byId($targetId);
        }

        if ($target->role() === MembershipRole::OWNER && $newRole !== MembershipRole::OWNER) {
            $owners = $this->memberships->countOwnersInOrganization($organization->id);
            if ($owners === 1) {
                throw LastOwnerCannotBeRevokedException::forOrganization($organization->id);
            }
        }

        return $this->tx->transactional(function () use ($target, $newRole): MembershipDTO {
            $target->changeRole($newRole);
            $this->memberships->save($target);
            $this->dispatcher->dispatchAll($target->pullDomainEvents());

            return MembershipDTO::fromEntity($target);
        });
    }
}
