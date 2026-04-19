<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\GetOrganization;

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;

/**
 * Handler получения организации по id. Возвращает OrganizationDTO.
 */
final readonly class GetOrganizationHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {}

    public function handle(GetOrganizationQuery $query): OrganizationDTO
    {
        $id = new OrganizationId($query->organizationId);
        $organization = $this->organizations->findById($id);
        if ($organization === null) {
            throw OrganizationNotFoundException::byId($id);
        }

        return OrganizationDTO::fromEntity($organization);
    }
}
