<?php

declare(strict_types=1);

namespace App\Modules\Booking\Domain\Specification;

use App\Modules\Booking\Domain\Entity\Booking;

/**
 * Контракт политики отмены — комбинирует спецификации (Not Completed AND Within Window).
 * Конкретная реализация в том же неймспейсе. Интерфейс нужен чтобы Booking aggregate
 * не зависел от конкретной реализации (тесты могут мокать).
 */
interface CancellationPolicy
{
    public function isSatisfiedBy(Booking $booking): bool;

    /**
     * Причина отказа после false-ответа isSatisfiedBy.
     */
    public function failureReason(): ?string;
}
