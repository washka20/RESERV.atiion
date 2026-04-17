<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\ListTimeSlots;

use App\Modules\Booking\Application\DTO\TimeSlotDTO;
use App\Modules\Booking\Application\DTO\TimeSlotListResult;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Handler admin/catalog-списка временных слотов. Read-side без Eloquent (ADR-007).
 */
final readonly class ListTimeSlotsHandler
{
    public function handle(ListTimeSlotsQuery $query): TimeSlotListResult
    {
        $builder = DB::table('time_slots');

        if ($query->serviceId !== null) {
            $builder->where('service_id', $query->serviceId);
        }
        if ($query->dateFrom !== null) {
            $builder->where('start_at', '>=', $query->dateFrom);
        }
        if ($query->dateTo !== null) {
            $builder->where('start_at', '<=', $query->dateTo);
        }
        if ($query->isBooked !== null) {
            $builder->where('is_booked', $query->isBooked);
        }

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->count();

        $offset = max(0, ($query->page - 1) * $query->perPage);
        $rows = $builder
            ->orderBy('start_at')
            ->limit($query->perPage)
            ->offset($offset)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        $lastPage = $total > 0 ? (int) ceil($total / $query->perPage) : 1;

        return new TimeSlotListResult(
            data: $items,
            total: $total,
            page: $query->page,
            perPage: $query->perPage,
            lastPage: $lastPage,
        );
    }

    private function mapRow(stdClass $row): TimeSlotDTO
    {
        return new TimeSlotDTO(
            id: (string) $row->id,
            serviceId: (string) $row->service_id,
            startAt: (new DateTimeImmutable((string) $row->start_at))->format(DATE_ATOM),
            endAt: (new DateTimeImmutable((string) $row->end_at))->format(DATE_ATOM),
            isBooked: (bool) $row->is_booked,
            bookingId: $row->booking_id !== null ? (string) $row->booking_id : null,
        );
    }
}
