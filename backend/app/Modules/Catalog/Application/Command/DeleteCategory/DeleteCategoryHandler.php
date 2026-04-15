<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\DeleteCategory;

use App\Modules\Catalog\Domain\Exception\CategoryHasServicesException;
use App\Modules\Catalog\Domain\Exception\CategoryNotFoundException;
use App\Modules\Catalog\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;

final readonly class DeleteCategoryHandler
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private ServiceRepositoryInterface $services,
    ) {}

    /**
     * Удаляет категорию. Запрещено, если есть услуги в этой категории.
     *
     * @throws CategoryNotFoundException если категории нет
     * @throws CategoryHasServicesException если есть привязанные услуги
     */
    public function handle(DeleteCategoryCommand $command): void
    {
        $categoryId = new CategoryId($command->categoryId);
        $this->categories->findByIdOrFail($categoryId);

        $attached = $this->services->findByCategory($categoryId);
        if ($attached !== []) {
            throw CategoryHasServicesException::forCategory($categoryId);
        }

        $this->categories->delete($categoryId);
    }
}
