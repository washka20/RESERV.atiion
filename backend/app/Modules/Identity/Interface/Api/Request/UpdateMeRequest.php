<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация PUT /api/v1/auth/me. Partial update: null/absent — не менять.
 *
 * @property-read ?string $email
 * @property-read ?string $first_name
 * @property-read ?string $last_name
 * @property-read ?string $middle_name
 */
final class UpdateMeRequest extends FormRequest
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
            'email' => ['sometimes', 'email', 'max:255'],
            'first_name' => ['sometimes', 'string', 'min:1', 'max:60'],
            'last_name' => ['sometimes', 'string', 'min:1', 'max:60'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:60'],
        ];
    }
}
