<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация payload POST /api/v1/organizations.
 *
 * Локализованные name/description принимаются как объекты {ru, en?}.
 * ru обязателен (инвариант domain entity).
 *
 * @property-read array{ru: string, en?: string} $name
 * @property-read array{ru?: string, en?: string} $description
 * @property-read string $type
 * @property-read string $city
 * @property-read string $phone
 * @property-read string $email
 */
final class CreateOrganizationRequest extends FormRequest
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
            'name' => ['required', 'array'],
            'name.ru' => ['required', 'string', 'min:2', 'max:100'],
            'name.en' => ['nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'array'],
            'description.ru' => ['required_with:description', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['salon', 'rental', 'consult', 'other'])],
            'city' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'min:7', 'max:32'],
            'email' => ['required', 'email'],
        ];
    }
}
