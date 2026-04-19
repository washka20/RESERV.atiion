<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Command\AdminArchiveOrganization;

use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

/**
 * Archive organization по команде platform admin'а (Filament). Не требует
 * membership — авторизация выполняется Filament gate'ом (canArchive).
 * Идемпотентно: повторная архивация уже архивной org не эмитит event.
 */
final readonly class AdminArchiveOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
        private DomainEventDispatcherInterface $dispatcher,
        private TransactionManagerInterface $tx,
    ) {}

    public function handle(AdminArchiveOrganizationCommand $cmd): void
    {
        $id = new OrganizationId($cmd->organizationId);
        $organization = $this->organizations->findById($id);
        if ($organization === null) {
            throw OrganizationNotFoundException::byId($id);
        }

        $this->tx->transactional(function () use ($organization): void {
            $organization->archive();
            $this->organizations->save($organization);
            $this->dispatcher->dispatchAll($organization->pullDomainEvents());
        });
    }
}
