<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Exception;

use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке выполнить операцию над выплатой в недопустимом статусе.
 */
final class PayoutAlreadyProcessedException extends DomainException
{
    public static function from(PayoutStatus $currentStatus): self
    {
        return new self(sprintf('Payout already in status: %s', $currentStatus->value));
    }

    public function errorCode(): string
    {
        return 'PAYOUT_ALREADY_PROCESSED';
    }
}
