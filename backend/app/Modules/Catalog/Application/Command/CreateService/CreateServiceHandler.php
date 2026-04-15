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
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use InvalidArgumentException;

final readonly class CreateServiceHandler
{
    public function __construct(
        private ServiceRepositoryInterface $services,
        private CategoryRepositoryInterface $categories,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Создаёт услугу и сохраняет. Проверяет существование категории.
     *
     * @throws CategoryNotFoundException если category отсутствует
     * @throws InvalidArgumentException при несоответствии type и durationMinutes/totalQuantity
     */
    public function handle(CreateServiceCommand $command): string
    {
        $categoryId = new CategoryId($command->categoryId);
        $this->categories->findByIdOrFail($categoryId);

        $subcategoryId = $command->subcategoryId !== null
            ? new SubcategoryId($command->subcategoryId)
            : null;

        $price = Money::fromCents($command->priceAmount, $command->priceCurrency);
        $type = ServiceType::from($command->type);
        $id = ServiceId::generate();

        $service = match ($type) {
            ServiceType::TIME_SLOT => $this->createTimeSlot($command, $id, $price, $categoryId, $subcategoryId),
            ServiceType::QUANTITY => $this->createQuantity($command, $id, $price, $categoryId, $subcategoryId),
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
        );
    }

    private function createQuantity(
        CreateServiceCommand $command,
        ServiceId $id,
        Money $price,
        CategoryId $categoryId,
        ?SubcategoryId $subcategoryId,
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
        );
    }
}
