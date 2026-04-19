<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Api\Resource;

use App\Modules\Payment\Application\DTO\PayoutTransactionDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализация PayoutTransactionDTO для публичного API организации.
 *
 * Имена полей — snake_case в соответствии с envelope.
 *
 * @property PayoutTransactionDTO $resource
 */
final class PayoutTransactionResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var PayoutTransactionDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'booking_id' => $dto->bookingId,
            'gross_cents' => $dto->grossCents,
            'platform_fee_cents' => $dto->platformFeeCents,
            'net_cents' => $dto->netAmountCents,
            'currency' => $dto->currency,
            'status' => $dto->status,
            'scheduled_at' => $dto->scheduledAt,
            'paid_at' => $dto->paidAt,
            'created_at' => $dto->createdAt,
        ];
    }
}
