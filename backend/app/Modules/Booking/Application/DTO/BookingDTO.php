<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

use App\Modules\Booking\Domain\Entity\Booking;

/**
 * DTO с полной информацией о бронировании — для API read-side и возврата из CommandHandler.
 */
final readonly class BookingDTO
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $serviceId,
        public string $type,
        public string $status,
        public ?string $slotId,
        public ?string $startAt,
        public ?string $endAt,
        public ?string $checkIn,
        public ?string $checkOut,
        public ?int $quantity,
        public int $totalPriceAmount,
        public string $totalPriceCurrency,
        public ?string $notes,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromEntity(Booking $booking): self
    {
        return new self(
            id: $booking->id->toString(),
            userId: $booking->userId->toString(),
            serviceId: $booking->serviceId->toString(),
            type: $booking->type->value,
            status: $booking->status()->value,
            slotId: $booking->slotId?->toString(),
            startAt: $booking->timeRange?->startAt->format(DATE_ATOM),
            endAt: $booking->timeRange?->endAt->format(DATE_ATOM),
            checkIn: $booking->dateRange?->checkIn->format('Y-m-d'),
            checkOut: $booking->dateRange?->checkOut->format('Y-m-d'),
            quantity: $booking->quantity?->value,
            totalPriceAmount: $booking->totalPrice->amount(),
            totalPriceCurrency: $booking->totalPrice->currency(),
            notes: $booking->notes,
            createdAt: $booking->createdAt->format(DATE_ATOM),
            updatedAt: $booking->updatedAt()->format(DATE_ATOM),
        );
    }
}
