<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Listener;

use App\Modules\Booking\Application\Query\GetBookingById\GetBookingByIdQuery;
use App\Modules\Catalog\Application\Query\GetServiceOrganizationId\GetServiceOrganizationIdQuery;
use App\Modules\Payment\Application\Command\CreatePayoutTransaction\CreatePayoutTransactionCommand;
use App\Modules\Payment\Domain\Event\PaymentReceived;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;

/**
 * Listener события PaymentReceived — создаёт PayoutTransaction для организации.
 *
 * Резолвит organization_id через service_id booking'а. Если booking или service
 * не найден (удалён между оплатой и чтением) — no-op.
 *
 * Идемпотентность создания payout обеспечивается на уровне CreatePayoutTransactionHandler
 * (проверка findByBookingId перед insert).
 */
final readonly class CreatePayoutTransactionOnPaymentReceived
{
    public function __construct(
        private CommandBusInterface $commands,
        private QueryBusInterface $queries,
    ) {}

    public function handle(PaymentReceived $event): void
    {
        $booking = $this->queries->ask(
            new GetBookingByIdQuery($event->bookingId()->toString()),
        );
        if ($booking === null) {
            return;
        }

        $orgId = $this->queries->ask(
            new GetServiceOrganizationIdQuery($booking->serviceId),
        );
        if ($orgId === null) {
            return;
        }

        $this->commands->dispatch(new CreatePayoutTransactionCommand(
            paymentId: $event->id()->toString(),
            bookingId: $event->bookingId()->toString(),
            organizationId: $orgId,
            grossCents: $event->gross()->amount(),
            currency: $event->gross()->currency(),
        ));
    }
}
