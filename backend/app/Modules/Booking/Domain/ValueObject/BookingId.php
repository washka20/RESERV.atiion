<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\ValueObject;

use App\Shared\Domain\AggregateId;

/**
 * Идентификатор бронирования (UUID). Aggregate id для Booking.
 */
final class BookingId extends AggregateId {}
