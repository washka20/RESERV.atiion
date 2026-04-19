<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Repository;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\ValueObject\PaymentId;

/**
 * Контракт репозитория платежей. Реализация в Infrastructure/Persistence.
 */
interface PaymentRepositoryInterface
{
    /**
     * Сохраняет (insert или update) платёж. Upsert по первичному ключу PaymentId.
     */
    public function save(Payment $payment): void;

    /**
     * Находит платёж по идентификатору. Возвращает null, если не найден.
     */
    public function findById(PaymentId $id): ?Payment;

    /**
     * Находит платёж по идентификатору бронирования (1:1). Возвращает null, если нет.
     */
    public function findByBookingId(BookingId $bookingId): ?Payment;
}
