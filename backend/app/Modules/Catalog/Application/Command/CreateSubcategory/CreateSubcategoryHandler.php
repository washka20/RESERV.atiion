<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateSubcategory;

use App\Modules\Catalog\Domain\Entity\Subcategory;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class CreateSubcategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Добавляет подкатегорию в существующую категорию. Возвращает id новой подкатегории.
     *
     * @throws CategoryNotFoundException если родительской категории нет
     */
    public function handle(CreateSubcategoryCommand $command): string
    {
        $categoryId = new CategoryId($command->categoryId);
        $category = $this->categories->findByIdOrFail($categoryId);

        $subcategoryId = SubcategoryId::generate();
        $subcategory = Subcategory::create(
            $subcategoryId,
            $categoryId,
            $command->name,
            $command->slug,
            $command->sortOrder,
        );
        $category->addSubcategory($subcategory);

        $this->categories->save($category);
        $this->dispatcher->dispatchAll($category->pullDomainEvents());

        return $subcategoryId->toString();
    }
}
