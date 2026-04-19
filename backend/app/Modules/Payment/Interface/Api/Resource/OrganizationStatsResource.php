<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Api\Resource;

use App\Modules\Payment\Application\DTO\OrganizationStatsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализация OrganizationStatsDTO для Owner Dashboard API.
 *
 * Валюта фиксирована RUB на MVP (если мульти-валюта понадобится — расширим DTO).
 *
 * @property OrganizationStatsDTO $resource
 */
final class OrganizationStatsResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var OrganizationStatsDTO $dto */
        $dto = $this->resource;

        return [
            'revenue_30d_cents' => $dto->revenue30dCents,
            'platform_fee_30d_cents' => $dto->platformFee30dCents,
            'net_payout_30d_cents' => $dto->netPayout30dCents,
            'bookings_30d' => $dto->bookings30d,
            'conversion_rate' => $dto->conversionRate,
            'currency' => 'RUB',
        ];
    }
}
