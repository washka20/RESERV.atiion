<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\DTO;

/**
 * Обёртка для paginated-списков. Generic по T в PHPDoc, но данные — untyped array.
 *
 * @template T
 */
final readonly class PaginatedResultDTO
{
    /**
     * @param  list<T>  $data
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}
}
