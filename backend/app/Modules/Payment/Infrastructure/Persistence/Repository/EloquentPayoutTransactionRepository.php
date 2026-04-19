<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Repository;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Infrastructure\Persistence\Mapper\PayoutTransactionMapper;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutTransactionModel;

/**
 * Eloquent реализация PayoutTransactionRepositoryInterface.
 *
 * save() — upsert по id. Domain events публикуются Application handler'ом после save.
 */
final class EloquentPayoutTransactionRepository implements PayoutTransactionRepositoryInterface
{
    public function save(PayoutTransaction $payout): void
    {
        PayoutTransactionModel::query()->updateOrCreate(
            ['id' => $payout->id()->toString()],
            PayoutTransactionMapper::toArray($payout),
        );
    }

    public function findById(PayoutTransactionId $id): ?PayoutTransaction
    {
        $model = PayoutTransactionModel::query()->find($id->toString());

        return $model !== null ? PayoutTransactionMapper::toDomain($model) : null;
    }

    public function findByBookingId(BookingId $bookingId): ?PayoutTransaction
    {
        $model = PayoutTransactionModel::query()
            ->where('booking_id', $bookingId->toString())
            ->first();

        return $model !== null ? PayoutTransactionMapper::toDomain($model) : null;
    }
}
