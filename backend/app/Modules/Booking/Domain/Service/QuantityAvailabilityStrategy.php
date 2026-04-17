<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Service;

use App\Modules\Booking\Domain\Exception\InvalidBookingTypeException;
use App\Modules\Booking\Domain\Repository\BookingRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\DateRange;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;

/**
 * Стратегия проверки доступности для QUANTITY услуг.
 *
 * Считает available = totalQuantity(service) - sumActiveBooked(serviceId, dateRange).
 */
final readonly class QuantityAvailabilityStrategy implements AvailabilityStrategyInterface
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepo,
        private BookingRepositoryInterface $bookingRepo,
    ) {}

    public function check(ServiceId $serviceId, array $params): AvailabilityResult
    {
        $service = $this->serviceRepo->findById($serviceId);
        if ($service === null || $service->totalQuantity() === null) {
            throw InvalidBookingTypeException::mismatch('quantity', 'service has no total_quantity');
        }

        $range = DateRange::fromStrings(
            (string) ($params['check_in'] ?? ''),
            (string) ($params['check_out'] ?? ''),
        );
        $requested = (int) ($params['requested'] ?? 1);
        $total = $service->totalQuantity();
        $booked = $this->bookingRepo->sumActiveQuantityOverlapping($serviceId, $range);
        $available = $total - $booked;

        return new AvailabilityResult(
            available: $available >= $requested,
            details: [
                'total' => $total,
                'booked' => $booked,
                'available' => $available,
                'requested' => $requested,
            ],
        );
    }
}
