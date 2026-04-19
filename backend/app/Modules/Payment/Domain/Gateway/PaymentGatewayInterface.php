<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\Gateway;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;

/**
 * Абстракция платёжного шлюза. Реализации: NullPaymentGateway (MVP),
 * Stripe/ЮKassa/Tinkoff — будущие планы.
 */
interface PaymentGatewayInterface
{
    /**
     * Создаёт charge на заданную сумму для указанного booking.
     */
    public function createCharge(Money $amount, BookingId $bookingId, PaymentMethod $method): GatewayChargeResult;

    /**
     * Возвращает средства по providerRef ранее успешного charge.
     */
    public function refund(string $providerRef): GatewayRefundResult;
}
