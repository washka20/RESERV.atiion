<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\GetBookingById;

use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;

/**
 * Handler GetBookingByIdQuery — без ownership-проверки.
 *
 * Возвращает null вместо исключения — listener'у проще проверить null,
 * чем ловить BookingNotFoundException (идемпотентность при повторных событиях).
 */
final readonly class GetBookingByIdHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    public function handle(GetBookingByIdQuery $query): ?BookingDTO
    {
        $booking = $this->bookingRepo->findById(new BookingId($query->bookingId));

        return $booking !== null ? BookingDTO::fromEntity($booking) : null;
    }
}
