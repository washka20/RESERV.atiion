<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\CreateBooking;

/**
 * Команда создания бронирования.
 *
 * Для TIME_SLOT услуг: нужен $slotId. Поля checkIn/checkOut/quantity игнорируются.
 * Для QUANTITY услуг: нужны $checkIn, $checkOut, $quantity. Поле $slotId игнорируется.
 * Валидация соответствия производится handler'ом после определения типа услуги.
 */
final readonly class CreateBookingCommand
{
    public function __construct(
        public string $userId,
        public string $serviceId,
        public ?string $slotId = null,
        public ?string $checkIn = null,
        public ?string $checkOut = null,
        public ?int $quantity = null,
        public ?string $notes = null,
    ) {}
}
