<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\CheckAvailability;

/**
 * Запрос проверки доступности услуги.
 *
 * $params — type-specific:
 *  - TIME_SLOT: ['date' => 'YYYY-MM-DD']
 *  - QUANTITY:  ['check_in' => 'YYYY-MM-DD', 'check_out' => 'YYYY-MM-DD', 'requested' => int]
 */
final readonly class CheckAvailabilityQuery
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public string $serviceId,
        public array $params,
    ) {}
}
