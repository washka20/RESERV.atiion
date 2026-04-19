<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\RevokeMembership;

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
 * Отзывает membership у user'а. Permission: team.manage (только owner).
 *
 * Защита инварианта: нельзя revoke последнего owner'а (если в org всего один
 * owner — throws LastOwnerCannotBeRevokedException).
 *
 * Membership::revoke() записывает event ПЕРЕД delete чтобы event попал в
 * pullDomainEvents. Repository.delete() только удаляет row, events не эмитит.
 */
final readonly class RevokeMembershipHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(RevokeMembershipCommand $cmd): void
    {
        $slug = new OrganizationSlug($cmd->organizationSlug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        $actorId = new UserId($cmd->actorUserId);
        $actorMembership = $this->memberships->findByPair($actorId, $organization->id);
        if ($actorMembership === null || ! $actorMembership->role()->can('team.manage')) {
            throw new RuntimeException('Forbidden: only organization owner can revoke members');
        }

        $targetId = new MembershipId($cmd->targetMembershipId);
        $target = $this->memberships->findById($targetId);
        if ($target === null) {
            throw MembershipNotFoundException::byId($targetId);
        }

        if (! $target->organizationId->equals($organization->id)) {
            throw MembershipNotFoundException::byId($targetId);
        }

        if ($target->role() === MembershipRole::OWNER) {
            $owners = $this->memberships->countOwnersInOrganization($organization->id);
            if ($owners === 1) {
                throw LastOwnerCannotBeRevokedException::forOrganization($organization->id);
            }
        }

        $this->tx->transactional(function () use ($target): void {
            $target->revoke();
            $this->memberships->delete($target->id);
            $this->dispatcher->dispatchAll($target->pullDomainEvents());
        });
    }
}
