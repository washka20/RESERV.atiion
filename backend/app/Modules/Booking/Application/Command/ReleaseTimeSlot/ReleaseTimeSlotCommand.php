<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\ReleaseTimeSlot;

/**
 * Команда освобождения временного слота.
 *
 * Используется event listener'ами (при отмене бронирования) и admin-действиями.
 */
final readonly class ReleaseTimeSlotCommand
{
    public function __construct(
        public string $slotId,
    ) {}
}
