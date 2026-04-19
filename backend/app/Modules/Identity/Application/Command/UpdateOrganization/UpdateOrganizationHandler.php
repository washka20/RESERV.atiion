<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\UpdateOrganization;

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use RuntimeException;

/**
 * Обновляет public-профиль организации. Permission: settings.manage (owner/admin).
 */
final readonly class UpdateOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(UpdateOrganizationCommand $cmd): OrganizationDTO
    {
        $slug = new OrganizationSlug($cmd->organizationSlug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        $actorId = new UserId($cmd->actorUserId);
        $membership = $this->memberships->findByPair($actorId, $organization->id);
        if ($membership === null || ! $membership->role()->can('settings.manage')) {
            throw new RuntimeException('Forbidden: insufficient permissions to update organization');
        }

        return $this->tx->transactional(function () use ($cmd, $organization): OrganizationDTO {
            $organization->updateDetails(
                name: $cmd->name,
                description: $cmd->description,
                city: $cmd->city,
                district: $cmd->district,
                phone: $cmd->phone,
                email: $cmd->email,
            );

            $this->organizations->save($organization);
            $this->dispatcher->dispatchAll($organization->pullDomainEvents());

            return OrganizationDTO::fromEntity($organization);
        });
    }
}
