<?php

declare(strict_types=1);

namespace App\Modules\Payment\Infrastructure\Persistence\Mapper;

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PayoutStatus;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutTransactionModel;

/**
 * Bidirectional mapper PayoutTransaction <-> PayoutTransactionModel.
 */
final class PayoutTransactionMapper
{
    public static function toDomain(PayoutTransactionModel $model): PayoutTransaction
    {
        $currency = (string) $model->currency;

        return PayoutTransaction::reconstitute(
            id: new PayoutTransactionId((string) $model->id),
            bookingId: new BookingId((string) $model->booking_id),
            organizationId: new OrganizationId((string) $model->organization_id),
            paymentId: new PaymentId((string) $model->payment_id),
            gross: Money::fromCents((int) $model->gross_amount_cents, $currency),
            platformFee: Money::fromCents((int) $model->platform_fee_cents, $currency),
            net: Money::fromCents((int) $model->net_amount_cents, $currency),
            status: PayoutStatus::from((string) $model->status),
            scheduledAt: $model->scheduled_at?->toDateTimeImmutable(),
            paidAt: $model->paid_at?->toDateTimeImmutable(),
            failureReason: $model->failure_reason,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(PayoutTransaction $payout): array
    {
        return [
            'id' => $payout->id()->toString(),
            'booking_id' => $payout->bookingId()->toString(),
            'organization_id' => $payout->organizationId()->toString(),
            'payment_id' => $payout->paymentId()->toString(),
            'gross_amount_cents' => $payout->grossAmount()->amount(),
            'platform_fee_cents' => $payout->platformFee()->amount(),
            'net_amount_cents' => $payout->netAmount()->amount(),
            'currency' => $payout->grossAmount()->currency(),
            'status' => $payout->status()->value,
            'scheduled_at' => $payout->scheduledAt(),
            'paid_at' => $payout->paidAt(),
            'failure_reason' => $payout->failureReason(),
        ];
    }
}
