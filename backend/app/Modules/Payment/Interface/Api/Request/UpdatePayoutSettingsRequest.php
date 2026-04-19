<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Api\Request;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация payload PUT /api/v1/organizations/{slug}/payout-settings.
 *
 * Owner-only endpoint (проверяется middleware org.member:payouts.manage на уровне роутов).
 *
 * @property-read string $bank_name
 * @property-read string $account_number
 * @property-read string $account_holder
 * @property-read string $bic
 * @property-read string $payout_schedule
 * @property-read int $minimum_payout_cents
 */
final class UpdatePayoutSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|min:10|max:30',
            'account_holder' => 'required|string|max:255',
            'bic' => ['required', 'string', 'regex:/^\d{9}$/'],
            'payout_schedule' => 'required|string|in:weekly,biweekly,monthly,on_request',
            'minimum_payout_cents' => 'required|integer|min:0',
        ];
    }
}
