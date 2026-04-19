<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Gateway;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Gateway\GatewayChargeResult;
use App\Modules\Payment\Domain\Gateway\GatewayRefundResult;
use App\Modules\Payment\Domain\Gateway\PaymentGatewayInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

/**
 * MVP gateway: всегда успешно, логирует в channel 'payments'.
 * providerRef: 'null-{uuid}' для уникальности между charges.
 */
final class NullPaymentGateway implements PaymentGatewayInterface
{
    public function __construct(private readonly LoggerInterface $logger) {}

    public function createCharge(Money $amount, BookingId $bookingId, PaymentMethod $method): GatewayChargeResult
    {
        $providerRef = 'null-'.Str::uuid()->toString();
        $this->logger->info('NullPaymentGateway createCharge', [
            'booking_id' => (string) $bookingId,
            'amount_cents' => $amount->amount(),
            'currency' => $amount->currency(),
            'method' => $method->value,
            'provider_ref' => $providerRef,
        ]);

        return new GatewayChargeResult(true, $providerRef, null);
    }

    public function refund(string $providerRef): GatewayRefundResult
    {
        $this->logger->info('NullPaymentGateway refund', ['provider_ref' => $providerRef]);

        return new GatewayRefundResult(true, null);
    }
}
