<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\UpdateSubcategory;

use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use InvalidArgumentException;

final readonly class UpdateSubcategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Обновляет name/sortOrder подкатегории.
     *
     * @throws CategoryNotFoundException если категории нет
     * @throws InvalidArgumentException если подкатегории нет в категории
     */
    public function handle(UpdateSubcategoryCommand $command): void
    {
        $category = $this->categories->findByIdOrFail(new CategoryId($command->categoryId));
        $targetId = new SubcategoryId($command->subcategoryId);

        foreach ($category->subcategories() as $subcategory) {
            if ($subcategory->id()->equals($targetId)) {
                $subcategory->changeName($command->name);
                $subcategory->changeSortOrder($command->sortOrder);
                $this->categories->save($category);
                $this->dispatcher->dispatchAll($category->pullDomainEvents());

                return;
            }
        }

        throw new InvalidArgumentException(
            sprintf('Subcategory "%s" not found in category "%s"', $command->subcategoryId, $command->categoryId)
        );
    }
}
