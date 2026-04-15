<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\DeleteSubcategory;

use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\SubcategoryId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class DeleteSubcategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Удаляет подкатегорию из категории. Идемпотентно.
     *
     * @throws CategoryNotFoundException если категории нет
     */
    public function handle(DeleteSubcategoryCommand $command): void
    {
        $category = $this->categories->findByIdOrFail(new CategoryId($command->categoryId));
        $category->removeSubcategory(new SubcategoryId($command->subcategoryId));
        $this->categories->save($category);
        $this->dispatcher->dispatchAll($category->pullDomainEvents());
    }
}
