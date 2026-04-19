<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Exception;

use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке выполнить операцию над платежом в недопустимом статусе.
 */
final class PaymentAlreadyProcessedException extends DomainException
{
    public static function from(PaymentStatus $currentStatus): self
    {
        return new self(sprintf('Payment already in status: %s', $currentStatus->value));
    }

    public function errorCode(): string
    {
        return 'PAYMENT_ALREADY_PROCESSED';
    }
}
