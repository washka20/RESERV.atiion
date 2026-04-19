<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\VerifyOrganization;

use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * Проставляет verified=true на organization. Admin-only (проверка в middleware).
 */
final readonly class VerifyOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(VerifyOrganizationCommand $cmd): void
    {
        $id = new OrganizationId($cmd->organizationId);
        $organization = $this->organizations->findById($id);
        if ($organization === null) {
            throw OrganizationNotFoundException::byId($id);
        }

        $this->tx->transactional(function () use ($organization): void {
            $organization->verify();
            $this->organizations->save($organization);
            $this->dispatcher->dispatchAll($organization->pullDomainEvents());
        });
    }
}
