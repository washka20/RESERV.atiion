<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Infrastructure\Persistence\Repository;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\ImagePath;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Catalog\Infrastructure\Persistence\Mapper\ServiceMapper;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceImageModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class EloquentServiceRepository implements ServiceRepositoryInterface
{
    public function save(Service $service): void
    {
        DB::transaction(function () use ($service): void {
            $data = ServiceMapper::toPersistence($service);

            ServiceModel::query()->updateOrInsert(
                ['id' => $data['id']],
                $data,
            );

            $this->syncImages($service);
        });
    }

    public function findById(ServiceId $id): ?Service
    {
        $model = ServiceModel::with('images')->find($id->toString());

        return $model !== null ? ServiceMapper::toDomain($model) : null;
    }

    public function findByIdOrFail(ServiceId $id): Service
    {
        return $this->findById($id) ?? throw ServiceNotFoundException::byId($id);
    }

    /**
     * @return list<Service>
     */
    public function findByCategory(CategoryId $categoryId): array
    {
        $models = ServiceModel::with('images')
            ->where('category_id', $categoryId->toString())
            ->get();

        /** @var list<Service> $services */
        $services = [];
        foreach ($models as $model) {
            $services[] = ServiceMapper::toDomain($model);
        }

        return $services;
    }

    public function delete(ServiceId $id): void
    {
        ServiceModel::query()->where('id', $id->toString())->delete();
    }

    /**
     * Синхронизирует images сервиса: удаляет отсутствующие, добавляет новые, обновляет sort_order.
     */
    private function syncImages(Service $service): void
    {
        $serviceId = $service->id()->toString();

        /** @var array<int, string> $existingPaths */
        $existingPaths = ServiceImageModel::query()
            ->where('service_id', $serviceId)
            ->pluck('path')
            ->all();

        $current = array_map(
            static fn (ImagePath $p): string => $p->value(),
            $service->images(),
        );

        $toDelete = array_diff($existingPaths, $current);
        if ($toDelete !== []) {
            ServiceImageModel::query()
                ->where('service_id', $serviceId)
                ->whereIn('path', $toDelete)
                ->delete();
        }

        foreach ($current as $index => $path) {
            $existing = ServiceImageModel::query()
                ->where('service_id', $serviceId)
                ->where('path', $path)
                ->first();

            if ($existing === null) {
                ServiceImageModel::query()->insert([
                    'id' => Uuid::uuid4()->toString(),
                    'service_id' => $serviceId,
                    'path' => $path,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                ServiceImageModel::query()
                    ->where('id', $existing->id)
                    ->update([
                        'sort_order' => $index,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}
