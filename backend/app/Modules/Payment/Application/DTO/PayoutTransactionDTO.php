<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\DTO;

use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use DateTimeImmutable;

/**
 * DTO с полной информацией о выплате — для read-side API и результата CommandHandler.
 *
 * Хранит амоунты в копейках/центах (int), таймстэмпы в ISO-8601 строках для сериализации.
 */
final readonly class PayoutTransactionDTO
{
    public function __construct(
        public string $id,
        public string $bookingId,
        public string $organizationId,
        public string $paymentId,
        public int $grossCents,
        public int $platformFeeCents,
        public int $netAmountCents,
        public string $currency,
        public string $status,
        public ?string $scheduledAt,
        public ?string $paidAt,
        public ?string $createdAt,
    ) {}

    public static function fromEntity(PayoutTransaction $payout): self
    {
        return new self(
            id: $payout->id()->toString(),
            bookingId: $payout->bookingId()->toString(),
            organizationId: $payout->organizationId()->toString(),
            paymentId: $payout->paymentId()->toString(),
            grossCents: $payout->grossAmount()->amount(),
            platformFeeCents: $payout->platformFee()->amount(),
            netAmountCents: $payout->netAmount()->amount(),
            currency: $payout->grossAmount()->currency(),
            status: $payout->status()->value,
            scheduledAt: $payout->scheduledAt()?->format(DATE_ATOM),
            paidAt: $payout->paidAt()?->format(DATE_ATOM),
            createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
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
            organizationId: (string) $row->organization_id,
            paymentId: (string) $row->payment_id,
            grossCents: (int) $row->gross_amount_cents,
            platformFeeCents: (int) $row->platform_fee_cents,
            netAmountCents: (int) $row->net_amount_cents,
            currency: (string) $row->currency,
            status: (string) $row->status,
            scheduledAt: $row->scheduled_at !== null
                ? (new DateTimeImmutable((string) $row->scheduled_at))->format(DATE_ATOM)
                : null,
            paidAt: $row->paid_at !== null
                ? (new DateTimeImmutable((string) $row->paid_at))->format(DATE_ATOM)
                : null,
            createdAt: $row->created_at !== null
                ? (new DateTimeImmutable((string) $row->created_at))->format(DATE_ATOM)
                : null,
        );
    }
}
