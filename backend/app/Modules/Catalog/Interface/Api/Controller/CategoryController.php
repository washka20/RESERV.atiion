<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Api\Controller;

use App\Modules\Catalog\Application\DTO\CategoryWithSubcategoriesDTO;
use App\Modules\Catalog\Application\Query\GetCategoryBySlug\GetCategoryBySlugQuery;
use App\Modules\Catalog\Application\Query\ListCategories\ListCategoriesQuery;
use App\Modules\Catalog\Interface\Api\Resource\CategoryResource;
use App\Shared\Application\Bus\QueryBusInterface;
use Illuminate\Http\JsonResponse;

/**
 * Публичные эндпоинты дерева категорий и получения категории по slug.
 */
final class CategoryController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
    ) {}

    public function index(): JsonResponse
    {
        /** @var list<CategoryWithSubcategoriesDTO> $categories */
        $categories = $this->queryBus->ask(new ListCategoriesQuery);

        $items = [];
        foreach ($categories as $dto) {
            $items[] = CategoryResource::fromDTO($dto);
        }

        return $this->envelope($items);
    }

    public function show(string $slug): JsonResponse
    {
        /** @var CategoryWithSubcategoriesDTO|null $dto */
        $dto = $this->queryBus->ask(new GetCategoryBySlugQuery($slug));

        if ($dto === null) {
            return $this->error(
                'CATEGORY_NOT_FOUND',
                sprintf('Category with slug "%s" not found', $slug),
                404,
            );
        }

        return $this->envelope(CategoryResource::fromDTO($dto));
    }

    /**
     * @param  mixed  $data
     */
    private function envelope($data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null,
            'meta' => null,
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
