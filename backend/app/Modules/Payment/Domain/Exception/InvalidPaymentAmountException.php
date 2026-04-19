<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Exception;

use App\Shared\Domain\Exception\DomainException;

/**
 * Бросается при попытке инициировать платёж с неположительной суммой.
 */
final class InvalidPaymentAmountException extends DomainException
{
    public function errorCode(): string
    {
        return 'PAYMENT_INVALID_AMOUNT';
    }
}
