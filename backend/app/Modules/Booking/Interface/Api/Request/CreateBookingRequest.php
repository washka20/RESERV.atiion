<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация payload публичного эндпоинта POST /api/v1/bookings.
 *
 * Форма принимает объединённый payload для двух типов (time_slot / quantity).
 * required_if обеспечивает обязательность соответствующих полей в зависимости от $type.
 *
 * @property-read string $service_id
 * @property-read string $type
 * @property-read string|null $slot_id
 * @property-read string|null $check_in
 * @property-read string|null $check_out
 * @property-read int|null $quantity
 * @property-read string|null $notes
 */
final class CreateBookingRequest extends FormRequest
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
            'service_id' => ['required', 'uuid', 'exists:services,id'],
            'type' => ['required', Rule::in(['time_slot', 'quantity'])],
            'slot_id' => ['required_if:type,time_slot', 'nullable', 'uuid', 'exists:time_slots,id'],
            'check_in' => ['required_if:type,quantity', 'nullable', 'date_format:Y-m-d', 'after:today'],
            'check_out' => ['required_if:type,quantity', 'nullable', 'date_format:Y-m-d', 'after:check_in'],
            'quantity' => ['required_if:type,quantity', 'nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
