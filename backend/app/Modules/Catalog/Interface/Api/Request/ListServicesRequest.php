<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация query-параметров публичного эндпоинта GET /api/v1/services.
 *
 * @property-read string|null $categoryId
 * @property-read string|null $subcategoryId
 * @property-read string|null $type
 * @property-read string|null $search
 * @property-read int|null $minPrice
 * @property-read int|null $maxPrice
 * @property-read int|null $page
 * @property-read int|null $perPage
 */
final class ListServicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'categoryId' => ['nullable', 'string', 'uuid'],
            'subcategoryId' => ['nullable', 'string', 'uuid'],
            'type' => ['nullable', 'string', 'in:time_slot,quantity'],
            'search' => ['nullable', 'string', 'max:255'],
            'minPrice' => ['nullable', 'integer', 'min:0'],
            'maxPrice' => ['nullable', 'integer', 'min:0'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
