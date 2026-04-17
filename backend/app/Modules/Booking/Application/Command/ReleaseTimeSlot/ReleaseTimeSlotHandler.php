<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\ReleaseTimeSlot;

use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\SlotId;

/**
 * Освобождает временной слот (markAsFree).
 *
 * Идемпотентная операция — повторный вызов на свободном слоте допустим.
 */
final readonly class ReleaseTimeSlotHandler
{
    public function __construct(
        private TimeSlotRepositoryInterface $slotRepo,
    ) {}

    public function handle(ReleaseTimeSlotCommand $cmd): void
    {
        $this->slotRepo->markAsFree(new SlotId($cmd->slotId));
    }
}
