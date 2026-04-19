<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateService;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Domain\ValueObject\ServiceType;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Modules\Identity\Domain\Exception\OrganizationArchivedException;
use App\Modules\Identity\Domain\Exception\OrganizationNotFoundException;
use App\Modules\Identity\Domain\Repository\OrganizationRepositoryInterface;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use InvalidArgumentException;

final readonly class CreateServiceHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private CategoryRepositoryInterface $categories,
        private OrganizationRepositoryInterface $organizations,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Создаёт услугу и сохраняет. Проверяет существование категории и организации.
     *
     * Permission check (actor is member with services.create) — responsibility
     * вызывающего layer'а (middleware / admin UI). Handler гарантирует только
     * целостность: organization exists и не archived.
     *
     * @throws CategoryNotFoundException если category отсутствует
     * @throws OrganizationNotFoundException если organization отсутствует
     * @throws OrganizationArchivedException если organization archived
     * @throws InvalidArgumentException при несоответствии type и durationMinutes/totalQuantity
     */
    public function handle(CreateServiceCommand $command): string
    {
        $categoryId = new CategoryId($command->categoryId);
        $this->categories->findByIdOrFail($categoryId);

        $organizationId = new OrganizationId($command->organizationId);
        $organization = $this->organizations->findById($organizationId);
        if ($organization === null) {
            throw OrganizationNotFoundException::byId($organizationId);
        }
        if ($organization->isArchived()) {
            throw OrganizationArchivedException::forId($organizationId);
        }

        $subcategoryId = $command->subcategoryId !== null
            ? new SubcategoryId($command->subcategoryId)
            : null;

        $price = Money::fromCents($command->priceAmount, $command->priceCurrency);
        $type = ServiceType::from($command->type);
        $id = ServiceId::generate();

        $service = match ($type) {
            ServiceType::TIME_SLOT => $this->createTimeSlot($command, $id, $price, $categoryId, $subcategoryId, $organizationId),
            ServiceType::QUANTITY => $this->createQuantity($command, $id, $price, $categoryId, $subcategoryId, $organizationId),
        };

        $this->services->save($service);
        $this->dispatcher->dispatchAll($service->pullDomainEvents());

        return $service->id()->toString();
    }

    private function createTimeSlot(
        CreateServiceCommand $command,
        ServiceId $id,
        Money $price,
        CategoryId $categoryId,
        ?SubcategoryId $subcategoryId,
        OrganizationId $organizationId,
    ): Service {
        if ($command->durationMinutes === null) {
            throw new InvalidArgumentException('durationMinutes is required for TIME_SLOT service');
        }

        return Service::createTimeSlot(
            $id,
            $command->name,
            $command->description,
            $price,
            Duration::ofMinutes($command->durationMinutes),
            $categoryId,
            $subcategoryId,
            $organizationId,
        );
    }

    private function createQuantity(
        CreateServiceCommand $command,
        ServiceId $id,
        Money $price,
        CategoryId $categoryId,
        ?SubcategoryId $subcategoryId,
        OrganizationId $organizationId,
    ): Service {
        if ($command->totalQuantity === null) {
            throw new InvalidArgumentException('totalQuantity is required for QUANTITY service');
        }

        return Service::createQuantity(
            $id,
            $command->name,
            $command->description,
            $price,
            $command->totalQuantity,
            $categoryId,
            $subcategoryId,
            $organizationId,
        );
    }
}
