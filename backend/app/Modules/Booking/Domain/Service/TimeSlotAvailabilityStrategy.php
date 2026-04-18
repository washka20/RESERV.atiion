<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Service;

use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use DateTimeImmutable;

/**
 * Стратегия проверки доступности для TIME_SLOT услуг.
 *
 * Возвращает список свободных слотов на указанную дату.
 */
final readonly class TimeSlotAvailabilityStrategy implements AvailabilityStrategyInterface
{
    public function __construct(
        private TimeSlotRepositoryInterface $repo,
    ) {}

    public function check(ServiceId $serviceId, array $params): AvailabilityResult
    {
        $date = new DateTimeImmutable($params['date'] ?? 'today');
        $slots = $this->repo->findAvailableByServiceAndDate($serviceId, $date);

        return new AvailabilityResult(
            available: $slots !== [],
            details: ['slots' => $slots],
        );
    }
}
