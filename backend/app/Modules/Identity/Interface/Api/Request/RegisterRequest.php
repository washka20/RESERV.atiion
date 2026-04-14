<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @property-read string $email
 * @property-read string $password
 * @property-read string $first_name
 * @property-read string $last_name
 * @property-read string|null $middle_name
 */
final class RegisterRequest extends FormRequest
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
            'email' => ['required', 'email:rfc,strict', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'middle_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
