<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateCategory;

use App\Modules\Catalog\Domain\Entity\Category;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;

final readonly class CreateCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * Создаёт категорию и возвращает её id.
     */
    public function handle(CreateCategoryCommand $command): string
    {
        $category = Category::create(
            CategoryId::generate(),
            $command->name,
            $command->slug,
            $command->sortOrder,
        );

        $this->categories->save($category);
        $this->dispatcher->dispatchAll($category->pullDomainEvents());

        return $category->id()->toString();
    }
}
