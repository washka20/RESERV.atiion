<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\UpdateCategory;

use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class UpdateCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Обновляет имя и порядок сортировки категории.
     *
     * @throws CategoryNotFoundException если категории нет
     */
    public function handle(UpdateCategoryCommand $command): void
    {
        $category = $this->categories->findByIdOrFail(new CategoryId($command->categoryId));
        $category->changeName($command->name);
        $category->changeSortOrder($command->sortOrder);
        $this->categories->save($category);
        $this->dispatcher->dispatchAll($category->pullDomainEvents());
    }
}
