<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Api\Controller;

use App\Modules\Catalog\Application\DTO\PaginatedResultDTO;
use App\Modules\Catalog\Application\DTO\ServiceDTO;
use App\Modules\Catalog\Application\Query\GetService\GetServiceQuery;
use App\Modules\Catalog\Application\Query\ListServices\ListServicesQuery;
use App\Modules\Catalog\Domain\Exception\ServiceNotFoundException;
use App\Modules\Catalog\Interface\Api\Request\ListServicesRequest;
use App\Modules\Catalog\Interface\Api\Resource\ServiceListItemResource;
use App\Modules\Catalog\Interface\Api\Resource\ServiceResource;
use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Http\JsonResponse;

/**
 * Публичные эндпоинты списка и деталей услуг каталога.
 */
final class ServiceController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function index(ListServicesRequest $request): JsonResponse
    {
        $page = (int) ($request->input('page') ?? 1);
        $perPage = (int) ($request->input('perPage') ?? 20);

        $query = new ListServicesQuery(
            categoryId: $this->nullableString($request->input('categoryId')),
            subcategoryId: $this->nullableString($request->input('subcategoryId')),
            type: $this->nullableString($request->input('type')),
            isActive: true,
            search: $this->nullableString($request->input('search')),
            minPrice: $request->input('minPrice') !== null ? (int) $request->input('minPrice') : null,
            maxPrice: $request->input('maxPrice') !== null ? (int) $request->input('maxPrice') : null,
            page: $page,
            perPage: $perPage,
        );

        /** @var PaginatedResultDTO $result */
        $result = $this->queryBus->ask($query);

        $items = [];
        foreach ($result->data as $dto) {
            $items[] = ServiceListItemResource::fromDTO($dto);
        }

        return $this->envelope(
            data: $items,
            meta: [
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'last_page' => $result->perPage > 0
                    ? (int) max(1, (int) ceil($result->total / $result->perPage))
                    : 1,
            ],
        );
    }

    public function show(string $id): JsonResponse
    {
        try {
            /** @var ServiceDTO $dto */
            $dto = $this->queryBus->ask(new GetServiceQuery($id));
        } catch (ServiceNotFoundException $e) {
            return $this->error('SERVICE_NOT_FOUND', $e->getMessage(), 404);
        }

        return $this->envelope(ServiceResource::fromDTO($dto));
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return $value === '' ? null : $value;
    }

    /**
     * @param  mixed  $data
     * @param  array<string, mixed>|null  $meta
     */
    private function envelope($data, ?array $meta = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => $meta,
        ], $status);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => null,
            ],
            'meta' => null,
        ], $status);
    }
}
