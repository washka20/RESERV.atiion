<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация payload POST /api/v1/organizations/{slug}/members/invite.
 *
 * Роль owner недопустима на уровне API — owner создаётся только при
 * CreateOrganization или через change-role существующего membership.
 */
final class InviteMemberRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'role' => ['required', Rule::in(['admin', 'staff', 'viewer'])],
        ];
    }
}
