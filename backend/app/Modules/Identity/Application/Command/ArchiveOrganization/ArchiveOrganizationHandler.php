<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\ArchiveOrganization;

use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Архивирует организацию. Permission: organization.archive (только owner).
 */
final readonly class ArchiveOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(ArchiveOrganizationCommand $cmd): void
    {
        $slug = new OrganizationSlug($cmd->organizationSlug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        $actorId = new UserId($cmd->actorUserId);
        $membership = $this->memberships->findByPair($actorId, $organization->id);
        if ($membership === null || ! $membership->role()->can('organization.archive')) {
            throw new RuntimeException('Forbidden: only organization owner can archive');
        }

        $this->tx->transactional(function () use ($organization): void {
            $organization->archive();
            $this->organizations->save($organization);
            $this->dispatcher->dispatchAll($organization->pullDomainEvents());
        });
    }
}
