<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Repository;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Infrastructure\Persistence\Mapper\PaymentMapper;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;

/**
 * Eloquent реализация PaymentRepositoryInterface.
 *
 * save() — upsert по id через updateOrCreate.
 * Событие доменное публикует Application Handler после успешного save, не репозиторий.
 */
final class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function save(Payment $payment): void
    {
        PaymentModel::query()->updateOrCreate(
            ['id' => $payment->id()->toString()],
            PaymentMapper::toArray($payment),
        );
    }

    public function findById(PaymentId $id): ?Payment
    {
        $model = PaymentModel::query()->find($id->toString());

        return $model !== null ? PaymentMapper::toDomain($model) : null;
    }

    public function findByBookingId(BookingId $bookingId): ?Payment
    {
        $model = PaymentModel::query()
            ->where('booking_id', $bookingId->toString())
            ->first();

        return $model !== null ? PaymentMapper::toDomain($model) : null;
    }
}
