<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация payload PATCH /api/v1/organizations/{slug}.
 *
 * Все поля опциональны (sometimes), но если присутствуют — валидируются как при создании.
 * При обновлении имени всегда нужен ключ ru (invariant на уровне domain).
 */
final class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'array'],
            'name.ru' => ['required_with:name', 'string', 'min:2', 'max:100'],
            'name.en' => ['nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'array'],
            'description.ru' => ['required_with:description', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'city' => ['sometimes', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'phone' => ['sometimes', 'string', 'min:7', 'max:32'],
            'email' => ['sometimes', 'email'],
        ];
    }
}
