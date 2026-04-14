<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;

final class RefreshRequest extends FormRequest
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
            'refresh_token' => ['required', 'string'],
        ];
    }
}
