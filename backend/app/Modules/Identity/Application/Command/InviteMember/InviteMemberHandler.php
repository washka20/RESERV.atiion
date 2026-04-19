<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\InviteMember;

use App\Modules\Identity\Application\DTO\MembershipDTO;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Exception\MembershipAlreadyExistsException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Exception\UserNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\Email;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Приглашает existing user'а в организацию с заданной ролью (admin/staff/viewer).
 * Owner granted только через CreateOrganization. Unregistered user — throws
 * UserNotFoundException (magic-link flow откладывается на Plan 14).
 */
final readonly class InviteMemberHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private UserRepositoryInterface $users,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(InviteMemberCommand $cmd): MembershipDTO
    {
        $role = MembershipRole::from($cmd->role);
        if ($role === MembershipRole::OWNER) {
            throw new InvalidArgumentException('Cannot invite as owner — use CreateOrganization or role-change');
        }

        $slug = new OrganizationSlug($cmd->organizationSlug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        $actorId = new UserId($cmd->actorUserId);
        $actorMembership = $this->memberships->findByPair($actorId, $organization->id);
        if ($actorMembership === null || ! $actorMembership->role()->can('team.manage')) {
            throw new RuntimeException('Forbidden: only organization owner can invite members');
        }

        $email = new Email($cmd->inviteeEmail);
        $invitee = $this->users->findByEmail($email);
        if ($invitee === null) {
            throw UserNotFoundException::byEmail($email);
        }

        $existing = $this->memberships->findByPair($invitee->id(), $organization->id);
        if ($existing !== null) {
            throw MembershipAlreadyExistsException::forPair($invitee->id(), $organization->id);
        }

        return $this->tx->transactional(function () use ($invitee, $organization, $role, $actorId): MembershipDTO {
            $membership = Membership::grant(
                id: MembershipId::generate(),
                userId: $invitee->id(),
                organizationId: $organization->id,
                role: $role,
                invitedBy: $actorId,
            );

            $this->memberships->save($membership);
            $this->dispatcher->dispatchAll($membership->pullDomainEvents());

            return MembershipDTO::fromEntity($membership);
        });
    }
}
