<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Command\GenerateTimeSlots;

/**
 * Команда массовой генерации временных слотов для TIME_SLOT услуги.
 *
 * Обходит каждый день диапазона [$dateFrom, $dateTo] и создаёт слоты
 * длительностью $slotDurationMinutes с опциональным перерывом $breakMinutes.
 *
 * $excludeDaysOfWeek — массив в формате PHP 'w' (0 = воскресенье .. 6 = суббота).
 */
final readonly class GenerateTimeSlotsCommand
{
    /**
     * @param  int[]  $excludeDaysOfWeek  0=Sunday..6=Saturday — exclude these weekdays
     */
    public function __construct(
        public string $serviceId,
        public string $dateFrom,
        public string $dateTo,
        public string $timeFrom,
        public string $timeTo,
        public int $slotDurationMinutes,
        public int $breakMinutes = 0,
        public array $excludeDaysOfWeek = [],
    ) {}
}
