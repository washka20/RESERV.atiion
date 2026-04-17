<?php

declare(strict_types=1);

use App\Modules\Booking\Application\Command\ReleaseTimeSlot\ReleaseTimeSlotCommand;
use App\Modules\Booking\Application\Command\ReleaseTimeSlot\ReleaseTimeSlotHandler;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\SlotId;

it('calls markAsFree with correct SlotId', function (): void {
    $slotId = SlotId::generate();

    $slotRepo = mock(TimeSlotRepositoryInterface::class);
    $slotRepo->shouldReceive('markAsFree')
        ->once()
        ->with(Mockery::on(fn (SlotId $id): bool => $id->toString() === $slotId->toString()));

    $handler = new ReleaseTimeSlotHandler($slotRepo);
    $handler->handle(new ReleaseTimeSlotCommand($slotId->toString()));
});
