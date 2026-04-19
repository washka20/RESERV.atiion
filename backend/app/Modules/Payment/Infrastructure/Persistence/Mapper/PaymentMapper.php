<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Mapper;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\ValueObject\MarketplaceFee;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;

/**
 * Bidirectional mapper Payment <-> PaymentModel.
 *
 * В БД хранятся snapshot-поля platform_fee_cents / net_amount_cents, чтобы избежать
 * пересчёта при чтении отчётов. Однако Payment domain рассчитывает их динамически через
 * MarketplaceFee::calculate(gross, feePercent) — mapper не возвращает их в доменную модель,
 * но сохраняет актуальный snapshot в БД через toArray().
 */
final class PaymentMapper
{
    public static function toDomain(PaymentModel $model): Payment
    {
        $gross = Money::fromCents((int) $model->amount_cents, (string) $model->currency);
        $feePercent = Percentage::fromInt((int) $model->marketplace_fee_percent);

        // snapshot fee/net проверяется в тестах, но воссоздаётся на лету в Payment.
        // Вызов ниже гарантирует что VO собирается валидно (detect drift) — результат не нужен.
        MarketplaceFee::calculate($gross, $feePercent);

        return Payment::reconstitute(
            id: new PaymentId((string) $model->id),
            bookingId: new BookingId((string) $model->booking_id),
            gross: $gross,
            method: PaymentMethod::from((string) $model->method),
            feePercent: $feePercent,
            status: PaymentStatus::from((string) $model->status),
            providerRef: $model->provider_ref,
            paidAt: $model->paid_at?->toDateTimeImmutable(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(Payment $payment): array
    {
        $fee = MarketplaceFee::calculate($payment->gross(), $payment->feePercent());

        return [
            'id' => $payment->id()->toString(),
            'booking_id' => $payment->bookingId()->toString(),
            'amount_cents' => $payment->gross()->amount(),
            'currency' => $payment->gross()->currency(),
            'status' => $payment->status()->value,
            'method' => $payment->method()->value,
            'provider_ref' => $payment->providerRef(),
            'marketplace_fee_percent' => $payment->feePercent()->value(),
            'platform_fee_cents' => $fee->fee()->amount(),
            'net_amount_cents' => $fee->net()->amount(),
            'paid_at' => $payment->paidAt(),
        ];
    }
}
