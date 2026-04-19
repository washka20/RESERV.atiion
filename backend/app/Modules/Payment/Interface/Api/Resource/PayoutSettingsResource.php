<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Api\Resource;

use App\Modules\Payment\Application\DTO\PayoutSettingsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализация PayoutSettingsDTO для API.
 *
 * Возвращает ТОЛЬКО маскированный номер счёта (account_number_masked).
 * Plaintext номер никогда не покидает сервер через этот endpoint.
 *
 * @property PayoutSettingsDTO $resource
 */
final class PayoutSettingsResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PayoutSettingsDTO $dto */
        $dto = $this->resource;

        return [
            'bank_name' => $dto->bankName,
            'account_number_masked' => $dto->accountNumberMasked,
            'account_holder' => $dto->accountHolder,
            'bic' => $dto->bic,
            'payout_schedule' => $dto->payoutSchedule,
            'minimum_payout_cents' => $dto->minimumPayoutCents,
        ];
    }
}
