<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Resource;

use App\Modules\Booking\Application\DTO\BookingListItemDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализует BookingListItemDTO для list-эндпоинтов (облегчённая форма без notes/updatedAt/endAt).
 *
 * @property BookingListItemDTO $resource
 */
final class BookingListItemResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookingListItemDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'service_id' => $dto->serviceId,
            'type' => $dto->type,
            'status' => $dto->status,
            'slot_id' => $dto->slotId,
            'start_at' => $dto->startAt,
            'check_in' => $dto->checkIn,
            'check_out' => $dto->checkOut,
            'quantity' => $dto->quantity,
            'total_price' => [
                'amount' => $dto->totalPriceAmount,
                'currency' => $dto->totalPriceCurrency,
            ],
            'created_at' => $dto->createdAt,
        ];
    }
}
