<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация query-параметров публичного эндпоинта GET /api/v1/services/{service}/availability.
 *
 * Полиморфный payload по $type:
 *  - time_slot: требует `date` (Y-m-d, сегодня или позже)
 *  - quantity:  требует `check_in`, `check_out` (Y-m-d, check_out > check_in), `requested` (int ≥ 1)
 *
 * @property-read string $type
 * @property-read string|null $date
 * @property-read string|null $check_in
 * @property-read string|null $check_out
 * @property-read int|null $requested
 */
final class CheckAvailabilityRequest extends FormRequest
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
            'type' => ['required', Rule::in(['time_slot', 'quantity'])],
            'date' => ['required_if:type,time_slot', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_in' => ['required_if:type,quantity', 'nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required_if:type,quantity', 'nullable', 'date_format:Y-m-d', 'after:check_in'],
            'requested' => ['required_if:type,quantity', 'nullable', 'integer', 'min:1'],
        ];
    }
}
