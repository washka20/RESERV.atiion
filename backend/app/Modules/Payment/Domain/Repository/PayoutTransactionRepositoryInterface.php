<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Repository;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;

/**
 * Репозиторий {@see PayoutTransaction}.
 *
 * Один payout на booking (UNIQUE на уровне таблицы) — findByBookingId возвращает
 * единственный или null, не коллекцию.
 */
interface PayoutTransactionRepositoryInterface
{
    public function save(PayoutTransaction $payout): void;

    public function findById(PayoutTransactionId $id): ?PayoutTransaction;

    public function findByBookingId(BookingId $bookingId): ?PayoutTransaction;
}
