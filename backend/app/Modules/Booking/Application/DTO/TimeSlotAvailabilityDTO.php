<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\DTO;

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Service\AvailabilityResult;

/**
 * DTO доступности для TIME_SLOT услуг.
 *
 * Возвращает список свободных слотов с ISO-датами начала/конца.
 */
final readonly class TimeSlotAvailabilityDTO extends AvailabilityDTO
{
    /**
     * @param  list<array{id: string, start_at: string, end_at: string}>  $slots
     */
    public function __construct(
        bool $available,
        public array $slots,
    ) {
        parent::__construct('time_slot', $available);
    }

    /**
     * Собирает DTO из AvailabilityResult стратегии TIME_SLOT.
     *
     * Ожидает в $result->details ключ 'slots' со списком TimeSlot entity.
     */
    public static function fromResult(AvailabilityResult $result): self
    {
        /** @var list<TimeSlot> $slots */
        $slots = $result->details['slots'] ?? [];

        $mapped = [];
        foreach ($slots as $slot) {
            $mapped[] = [
                'id' => $slot->id->toString(),
                'start_at' => $slot->range->startAt->format(DATE_ATOM),
                'end_at' => $slot->range->endAt->format(DATE_ATOM),
            ];
        }

        return new self(
            available: $result->available,
            slots: $mapped,
        );
    }
}
