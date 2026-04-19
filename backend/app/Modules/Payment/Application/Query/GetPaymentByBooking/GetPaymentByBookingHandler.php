<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\GetPaymentByBooking;

use App\Modules\Payment\Application\DTO\PaymentDTO;
use Illuminate\Database\ConnectionInterface;

/**
 * Handler GetPaymentByBookingQuery.
 *
 * Read-side CQRS: обходит Eloquent, читает через DB connection → PaymentDTO::fromRow.
 * Возвращает null если для booking нет платежа.
 */
final readonly class GetPaymentByBookingHandler
{
    public function __construct(private ConnectionInterface $db) {}

    public function handle(GetPaymentByBookingQuery $query): ?PaymentDTO
    {
        $row = $this->db->table('payments')
            ->where('booking_id', $query->bookingId->toString())
            ->first();

        return $row !== null ? PaymentDTO::fromRow($row) : null;
    }
}
