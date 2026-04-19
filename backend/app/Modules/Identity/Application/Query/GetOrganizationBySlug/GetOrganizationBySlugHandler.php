<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Query\GetOrganizationBySlug;

use App\Modules\Identity\Application\DTO\OrganizationDTO;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationSlug;

/**
 * Handler получения организации по slug.
 */
final readonly class GetOrganizationBySlugHandler
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {}

    public function handle(GetOrganizationBySlugQuery $query): OrganizationDTO
    {
        $slug = new OrganizationSlug($query->slug);
        $organization = $this->organizations->findBySlug($slug);
        if ($organization === null) {
            throw OrganizationNotFoundException::bySlug($slug);
        }

        return OrganizationDTO::fromEntity($organization);
    }
}
