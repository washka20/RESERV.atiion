<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\ValueObject\MarketplaceFee;

/**
 * DTO с полной информацией о платеже — для read-side API и результата CommandHandler.
 *
 * Формат payload-а Query/Command слоёв: snake_case имён зарезервирован за HTTP envelope,
 * внутри DTO — camelCase в PHP, но fromRow читает snake_case из БД.
 */
final readonly class PaymentDTO
{
    public function __construct(
        public string $id,
        public string $bookingId,
        public int $amountCents,
        public string $currency,
        public string $status,
        public string $method,
        public int $feePercent,
        public int $platformFeeCents,
        public int $netAmountCents,
        public ?string $providerRef,
        public ?string $paidAt,
        public string $createdAt,
    ) {}

    public static function fromEntity(Payment $payment): self
    {
        $fee = MarketplaceFee::calculate($payment->gross(), $payment->feePercent());

        return new self(
            id: $payment->id()->toString(),
            bookingId: $payment->bookingId()->toString(),
            amountCents: $payment->gross()->amount(),
            currency: $payment->gross()->currency(),
            status: $payment->status()->value,
            method: $payment->method()->value,
            feePercent: $payment->feePercent()->value(),
            platformFeeCents: $fee->fee()->amount(),
            netAmountCents: $fee->net()->amount(),
            providerRef: $payment->providerRef(),
            paidAt: $payment->paidAt()?->format(DATE_ATOM),
            createdAt: (new \DateTimeImmutable)->format(DATE_ATOM),
        );
    }

    /**
     * Создаёт DTO из raw-строки БД (query-side, без Eloquent).
     */
    public static function fromRow(object $row): self
    {
        return new self(
            id: (string) $row->id,
            bookingId: (string) $row->booking_id,
            amountCents: (int) $row->amount_cents,
            currency: (string) $row->currency,
            status: (string) $row->status,
            method: (string) $row->method,
            feePercent: (int) $row->marketplace_fee_percent,
            platformFeeCents: (int) $row->platform_fee_cents,
            netAmountCents: (int) $row->net_amount_cents,
            providerRef: $row->provider_ref !== null ? (string) $row->provider_ref : null,
            paidAt: $row->paid_at !== null ? (new \DateTimeImmutable((string) $row->paid_at))->format(DATE_ATOM) : null,
            createdAt: (new \DateTimeImmutable((string) $row->created_at))->format(DATE_ATOM),
        );
    }
}
