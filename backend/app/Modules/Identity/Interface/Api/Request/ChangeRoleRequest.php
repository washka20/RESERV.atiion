<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация payload PATCH /api/v1/organizations/{slug}/members/{id}/role.
 * Допускается передача owner — handler enforce'ит last-owner инвариант.
 */
final class ChangeRoleRequest extends FormRequest
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
            'role' => ['required', Rule::in(['owner', 'admin', 'staff', 'viewer'])],
        ];
    }
}
