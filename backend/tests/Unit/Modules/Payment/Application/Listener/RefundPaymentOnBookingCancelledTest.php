<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Event\BookingCancelled;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Payment\Application\Command\RefundPayment\RefundPaymentCommand;
use App\Modules\Payment\Application\DTO\PaymentDTO;
use App\Modules\Payment\Application\Listener\RefundPaymentOnBookingCancelled;
use App\Modules\Payment\Application\Query\GetPaymentByBooking\GetPaymentByBookingQuery;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Shared\Application\Bus\CommandBusInterface;
use App\Shared\Application\Bus\QueryBusInterface;

if (! function_exists('makeRefundListenerPaymentDto')) {
    function makeRefundListenerPaymentDto(string $id, string $status): PaymentDTO
    {
        return new PaymentDTO(
            id: $id,
            bookingId: 'b',
            amountCents: 1000,
            currency: 'RUB',
            status: $status,
            method: 'card',
            feePercent: 10,
            platformFeeCents: 100,
            netAmountCents: 900,
            providerRef: null,
            paidAt: null,
            createdAt: (new DateTimeImmutable)->format(DATE_ATOM),
        );
    }
}

it('dispatches RefundPaymentCommand when payment is paid', function (): void {
    $bookingId = BookingId::generate();
    $paymentId = PaymentId::generate();
    $event = new BookingCancelled($bookingId, BookingType::TIME_SLOT, null, new DateTimeImmutable);

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')
        ->once()
        ->with(Mockery::on(fn (GetPaymentByBookingQuery $q) => $q->bookingId->equals($bookingId)))
        ->andReturn(makeRefundListenerPaymentDto($paymentId->toString(), PaymentStatus::PAID->value));

    $captured = null;
    $commands = mock(CommandBusInterface::class);
    $commands->shouldReceive('dispatch')
        ->once()
        ->andReturnUsing(function (RefundPaymentCommand $cmd) use (&$captured): void {
            $captured = $cmd;
        });

    (new RefundPaymentOnBookingCancelled($commands, $queries))->handle($event);

    expect($captured)->not->toBeNull()
        ->and($captured->id->toString())->toBe($paymentId->toString());
});

it('is no-op when payment not found', function (): void {
    $event = new BookingCancelled(BookingId::generate(), BookingType::TIME_SLOT, null, new DateTimeImmutable);

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')->once()->andReturn(null);

    $commands = mock(CommandBusInterface::class);
    $commands->shouldNotReceive('dispatch');

    (new RefundPaymentOnBookingCancelled($commands, $queries))->handle($event);
});

it('is no-op when payment is pending (not paid)', function (): void {
    $event = new BookingCancelled(BookingId::generate(), BookingType::TIME_SLOT, null, new DateTimeImmutable);

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')->once()->andReturn(makeRefundListenerPaymentDto('p', PaymentStatus::PENDING->value));

    $commands = mock(CommandBusInterface::class);
    $commands->shouldNotReceive('dispatch');

    (new RefundPaymentOnBookingCancelled($commands, $queries))->handle($event);
});

it('is no-op when payment already refunded', function (): void {
    $event = new BookingCancelled(BookingId::generate(), BookingType::TIME_SLOT, null, new DateTimeImmutable);

    $queries = mock(QueryBusInterface::class);
    $queries->shouldReceive('ask')->once()->andReturn(makeRefundListenerPaymentDto('p', PaymentStatus::REFUNDED->value));

    $commands = mock(CommandBusInterface::class);
    $commands->shouldNotReceive('dispatch');

    (new RefundPaymentOnBookingCancelled($commands, $queries))->handle($event);
});
