<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Resource;

use App\Modules\Booking\Application\DTO\BookingDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Сериализует BookingDTO в snake_case JSON для публичного API.
 *
 * @property BookingDTO $resource
 */
final class BookingResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var BookingDTO $dto */
        $dto = $this->resource;

        return [
            'id' => $dto->id,
            'user_id' => $dto->userId,
            'service_id' => $dto->serviceId,
            'type' => $dto->type,
            'status' => $dto->status,
            'slot_id' => $dto->slotId,
            'start_at' => $dto->startAt,
            'end_at' => $dto->endAt,
            'check_in' => $dto->checkIn,
            'check_out' => $dto->checkOut,
            'quantity' => $dto->quantity,
            'total_price' => [
                'amount' => $dto->totalPriceAmount,
                'currency' => $dto->totalPriceCurrency,
            ],
            'notes' => $dto->notes,
            'created_at' => $dto->createdAt,
            'updated_at' => $dto->updatedAt,
        ];
    }
}
