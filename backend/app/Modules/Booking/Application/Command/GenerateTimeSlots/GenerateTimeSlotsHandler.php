<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\GenerateTimeSlots;

use App\Modules\Booking\Domain\Entity\TimeSlot;
use App\Modules\Booking\Domain\Event\TimeSlotGenerated;
use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Shared\Application\Event\DomainEventDispatcherInterface;
use DateInterval;
use DateTimeImmutable;

/**
 * Генерирует массив TimeSlot для услуги на каждый день в диапазоне дат.
 *
 * Для каждого дня (не попадающего в $excludeDaysOfWeek) обходит окно
 * [timeFrom..timeTo] шагами slotDuration + break, создавая слоты,
 * полностью помещающиеся в окно.
 *
 * Все слоты сохраняются одним saveMany. После успешного сохранения
 * публикуется TimeSlotGenerated с общим количеством.
 */
final readonly class GenerateTimeSlotsHandler
{
    public function __construct(
        private TimeSlotRepositoryInterface $slotRepo,
        private DomainEventDispatcherInterface $dispatcher,
    ) {}

    /**
     * @return int количество сгенерированных слотов
     */
    public function handle(GenerateTimeSlotsCommand $cmd): int
    {
        $serviceId = new ServiceId($cmd->serviceId);
        $dateFrom = new DateTimeImmutable($cmd->dateFrom);
        $dateTo = new DateTimeImmutable($cmd->dateTo);
        $stepMinutes = $cmd->slotDurationMinutes + $cmd->breakMinutes;
        $excluded = array_map(static fn (int $d): int => $d, $cmd->excludeDaysOfWeek);

        $slots = [];
        $oneDay = new DateInterval('P1D');

        for ($date = $dateFrom; $date <= $dateTo; $date = $date->add($oneDay)) {
            $weekday = (int) $date->format('w');
            if (in_array($weekday, $excluded, true)) {
                continue;
            }

            $dayStart = new DateTimeImmutable($date->format('Y-m-d').' '.$cmd->timeFrom.':00');
            $dayEnd = new DateTimeImmutable($date->format('Y-m-d').' '.$cmd->timeTo.':00');

            for ($slotStart = $dayStart; ; $slotStart = $slotStart->add(new DateInterval('PT'.$stepMinutes.'M'))) {
                $slotEnd = $slotStart->add(new DateInterval('PT'.$cmd->slotDurationMinutes.'M'));
                if ($slotEnd > $dayEnd) {
                    break;
                }

                $slots[] = TimeSlot::create(
                    SlotId::generate(),
                    $serviceId,
                    $slotStart,
                    $slotEnd,
                );
            }
        }

        $this->slotRepo->saveMany($slots);

        $this->dispatcher->dispatch(new TimeSlotGenerated(
            $serviceId,
            $dateFrom,
            $dateTo,
            count($slots),
            new DateTimeImmutable(),
        ));

        return count($slots);
    }
}
