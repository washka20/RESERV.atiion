<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\Repository;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

interface ServiceRepositoryInterface
{
    public function save(Service $service): void;

    public function findById(ServiceId $id): ?Service;

    /**
     * @throws ServiceNotFoundException если услуги нет
     */
    public function findByIdOrFail(ServiceId $id): Service;

    /**
     * @return list<Service>
     */
    public function findByCategory(CategoryId $categoryId): array;

    public function delete(ServiceId $id): void;
}
