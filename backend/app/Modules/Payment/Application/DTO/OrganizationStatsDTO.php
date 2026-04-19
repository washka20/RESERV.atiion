<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

/**
 * Агрегированная статистика организации за последние 30 дней.
 *
 * Используется в Owner Dashboard UI. Все денежные величины — в копейках (int).
 * topServices — до 5 позиций, каждая: {id: string, title: string, bookings: int}.
 */
final readonly class OrganizationStatsDTO
{
    /**
     * @param  list<array{id: string, title: string, bookings: int}>  $topServices
     */
    public function __construct(
        public int $revenue30dCents,
        public int $platformFee30dCents,
        public int $netPayout30dCents,
        public int $bookings30d,
        public float $conversionRate,
        public array $topServices,
    ) {}
}
