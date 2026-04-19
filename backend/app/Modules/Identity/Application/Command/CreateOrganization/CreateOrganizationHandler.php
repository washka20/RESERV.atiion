<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\CreateOrganization;

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Domain\Entity\Membership;
use App\Modules\Identity\Domain\Entity\Organization;
use App\Modules\Identity\Domain\Exception\UserNotFoundException;
use App\Modules\Identity\Domain\Repository\MembershipRepositoryInterface;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\Repository\UserRepositoryInterface;
use App\Modules\Identity\Domain\Service\SlugGeneratorInterface;
use App\Modules\Identity\Domain\ValueObject\MembershipId;
use App\Modules\Identity\Domain\ValueObject\MembershipRole;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\OrganizationType;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * Создаёт Organization и сразу grant'ит создателю роль OWNER в одной транзакции.
 *
 * Slug генерируется из name['ru'] через SlugGenerator. OrganizationCreated и
 * MembershipGranted events публикуются после commit'а транзакции.
 */
final readonly class CreateOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private UserRepositoryInterface $users,
        private SlugGeneratorInterface $slugGenerator,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(CreateOrganizationCommand $cmd): OrganizationDTO
    {
        $userId = new UserId($cmd->userId);
        $user = $this->users->findById($userId);
        if ($user === null) {
            throw UserNotFoundException::byId($userId);
        }

        return $this->tx->transactional(function () use ($cmd, $userId): OrganizationDTO {
            $slugSource = $cmd->name['ru'] ?? '';
            $slug = $this->slugGenerator->generate($slugSource);

            $organization = Organization::create(
                id: OrganizationId::generate(),
                slug: $slug,
                name: $cmd->name,
                description: $cmd->description,
                type: OrganizationType::from($cmd->type),
                city: $cmd->city,
                phone: $cmd->phone,
                email: $cmd->email,
            );

            $this->organizations->save($organization);

            $membership = Membership::grant(
                id: MembershipId::generate(),
                userId: $userId,
                organizationId: $organization->id,
                role: MembershipRole::OWNER,
            );

            $this->memberships->save($membership);

            $this->dispatcher->dispatchAll($organization->pullDomainEvents());
            $this->dispatcher->dispatchAll($membership->pullDomainEvents());

            return OrganizationDTO::fromEntity($organization);
        });
    }
}
