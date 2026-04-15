<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\ListServices;

use App\Modules\Catalog\Application\DTO\PaginatedResultDTO;
use App\Modules\Catalog\Application\DTO\ServiceListItemDTO;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Возвращает paginated-список услуг для каталога с опциональными фильтрами.
 *
 * Использует QueryBuilder (без Eloquent) согласно CQRS read-модели.
 * Images догружаются batch-запросом без N+1.
 */
final readonly class ListServicesHandler
{
    public function handle(ListServicesQuery $query): PaginatedResultDTO
    {
        $builder = DB::table('services as s')
            ->leftJoin('categories as c', 's.category_id', '=', 'c.id')
            ->leftJoin('subcategories as sub', 's.subcategory_id', '=', 'sub.id')
            ->select([
                's.id',
                's.name',
                's.price_amount',
                's.price_currency',
                's.type',
                's.is_active',
                'c.name as category_name',
                'sub.name as subcategory_name',
            ]);

        $this->applyFilters($builder, $query);

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->count();

        $offset = ($query->page - 1) * $query->perPage;
        $rows = $builder
            ->orderBy('s.created_at', 'desc')
            ->offset($offset)
            ->limit($query->perPage)
            ->get();

        $serviceIds = $rows->pluck('id')->map(static fn ($id) => (string) $id)->all();
        $primaryImages = $this->fetchPrimaryImages($serviceIds);

        $items = [];
        foreach ($rows as $row) {
            $serviceId = (string) $row->id;
            $items[] = new ServiceListItemDTO(
                id: $serviceId,
                name: (string) $row->name,
                priceAmount: (int) $row->price_amount,
                priceCurrency: (string) $row->price_currency,
                type: (string) $row->type,
                categoryName: (string) $row->category_name,
                primaryImage: $primaryImages[$serviceId] ?? null,
                isActive: (bool) $row->is_active,
            );
        }

        return new PaginatedResultDTO(
            data: $items,
            total: $total,
            page: $query->page,
            perPage: $query->perPage,
        );
    }

    private function applyFilters(Builder $builder, ListServicesQuery $query): void
    {
        if ($query->categoryId !== null) {
            $builder->where('s.category_id', $query->categoryId);
        }

        if ($query->subcategoryId !== null) {
            $builder->where('s.subcategory_id', $query->subcategoryId);
        }

        if ($query->type !== null) {
            $builder->where('s.type', $query->type);
        }

        if ($query->isActive !== null) {
            $builder->where('s.is_active', $query->isActive);
        }

        if ($query->minPrice !== null) {
            $builder->where('s.price_amount', '>=', $query->minPrice);
        }

        if ($query->maxPrice !== null) {
            $builder->where('s.price_amount', '<=', $query->maxPrice);
        }

        if ($query->search !== null && $query->search !== '') {
            $operator = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $builder->where(static function (Builder $sub) use ($query, $operator): void {
                $pattern = '%'.$query->search.'%';
                if ($operator === 'ilike') {
                    $sub->where('s.name', 'ilike', $pattern)
                        ->orWhere('s.description', 'ilike', $pattern);
                } else {
                    $sub->whereRaw('LOWER(s.name) LIKE LOWER(?)', [$pattern])
                        ->orWhereRaw('LOWER(s.description) LIKE LOWER(?)', [$pattern]);
                }
            });
        }
    }

    /**
     * @param  list<string>  $serviceIds
     * @return array<string, string>
     */
    private function fetchPrimaryImages(array $serviceIds): array
    {
        if ($serviceIds === []) {
            return [];
        }

        $rows = DB::table('service_images')
            ->select(['service_id', 'path', 'sort_order'])
            ->whereIn('service_id', $serviceIds)
            ->orderBy('service_id')
            ->orderBy('sort_order')
            ->get();

        $byService = [];
        foreach ($rows as $row) {
            $sid = (string) $row->service_id;
            if (! isset($byService[$sid])) {
                $byService[$sid] = (string) $row->path;
            }
        }

        return $byService;
    }
}
