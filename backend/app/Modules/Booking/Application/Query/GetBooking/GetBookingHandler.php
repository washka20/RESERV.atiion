<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\GetBooking;

use App\Modules\Booking\Application\DTO\BookingDTO;
use App\Modules\Booking\Domain\Exception\BookingNotFoundException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use RuntimeException;

/**
 * Handler получения бронирования по id с проверкой ownership.
 *
 * Admin видит любое бронирование; обычный пользователь — только своё.
 */
final readonly class GetBookingHandler
{
    public function __construct(
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    /**
     * @throws BookingNotFoundException если бронирования нет
     * @throws RuntimeException если не-админ запрашивает чужое бронирование
     */
    public function handle(GetBookingQuery $query): BookingDTO
    {
        $id = new BookingId($query->bookingId);
        $booking = $this->bookingRepo->findById($id);
        if ($booking === null) {
            throw BookingNotFoundException::byId($id);
        }

        if (! $query->isAdmin && $booking->userId->toString() !== $query->actorUserId) {
            throw new RuntimeException('Forbidden');
        }

        return BookingDTO::fromEntity($booking);
    }
}
