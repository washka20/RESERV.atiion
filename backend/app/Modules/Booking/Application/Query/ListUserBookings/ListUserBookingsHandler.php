<?php

declare(strict_types=1);

namespace App\Modules\Booking\Application\Query\ListUserBookings;

use App\Modules\Booking\Application\DTO\BookingListItemDTO;
use App\Modules\Booking\Application\DTO\BookingListResult;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Handler списка бронирований пользователя. Read-side без Eloquent (ADR-007).
 */
final readonly class ListUserBookingsHandler
{
    public function handle(ListUserBookingsQuery $query): BookingListResult
    {
        $builder = DB::table('bookings')->where('user_id', $query->userId);
        if ($query->status !== null) {
            $builder->where('status', $query->status);
        }

        $countBuilder = clone $builder;
        $total = (int) $countBuilder->count();

        $offset = max(0, ($query->page - 1) * $query->perPage);
        $rows = $builder
            ->orderByDesc('created_at')
            ->limit($query->perPage)
            ->offset($offset)
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->mapRow($row);
        }

        $lastPage = $total > 0 ? (int) ceil($total / $query->perPage) : 1;

        return new BookingListResult(
            data: $items,
            total: $total,
            page: $query->page,
            perPage: $query->perPage,
            lastPage: $lastPage,
        );
    }

    private function mapRow(stdClass $row): BookingListItemDTO
    {
        return new BookingListItemDTO(
            id: (string) $row->id,
            serviceId: (string) $row->service_id,
            type: (string) $row->type,
            status: (string) $row->status,
            slotId: $row->slot_id !== null ? (string) $row->slot_id : null,
            startAt: $this->toIsoOrNull($row->start_at ?? null),
            checkIn: $row->check_in !== null ? substr((string) $row->check_in, 0, 10) : null,
            checkOut: $row->check_out !== null ? substr((string) $row->check_out, 0, 10) : null,
            quantity: $row->quantity !== null ? (int) $row->quantity : null,
            totalPriceAmount: (int) round(((float) $row->total_price_amount) * 100),
            totalPriceCurrency: (string) $row->total_price_currency,
            createdAt: $this->toIsoOrFallback($row->created_at),
        );
    }

    private function toIsoOrNull(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return (new DateTimeImmutable($raw))->format(DATE_ATOM);
    }

    private function toIsoOrFallback(mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return (new DateTimeImmutable)->format(DATE_ATOM);
        }

        return (new DateTimeImmutable((string) $raw))->format(DATE_ATOM);
    }
}
