<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Api\Resource;

use App\Modules\Booking\Application\DTO\AvailabilityDTO;
use App\Modules\Booking\Application\DTO\QuantityAvailabilityDTO;
use App\Modules\Booking\Application\DTO\TimeSlotAvailabilityDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Полиморфная сериализация AvailabilityDTO.
 *
 * TimeSlotAvailabilityDTO → добавляет `slots` со списком свободных слотов.
 * QuantityAvailabilityDTO → добавляет total / booked / available_quantity / requested.
 *
 * @property AvailabilityDTO $resource
 */
final class AvailabilityResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var AvailabilityDTO $dto */
        $dto = $this->resource;

        $base = [
            'type' => $dto->type,
            'available' => $dto->available,
        ];

        if ($dto instanceof TimeSlotAvailabilityDTO) {
            return $base + ['slots' => $dto->slots];
        }

        if ($dto instanceof QuantityAvailabilityDTO) {
            return $base + [
                'total' => $dto->total,
                'booked' => $dto->booked,
                'available_quantity' => $dto->availableQuantity,
                'requested' => $dto->requested,
            ];
        }

        return $base;
    }
}
