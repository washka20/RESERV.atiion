<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

/**
 * Тип бронирования — зеркалит ServiceType из Catalog BC.
 *
 * TIME_SLOT — бронирование конкретного временного слота.
 * QUANTITY — бронирование количества единиц на диапазон дат.
 */
enum BookingType: string
{
    case TIME_SLOT = 'time_slot';
    case QUANTITY = 'quantity';
}
