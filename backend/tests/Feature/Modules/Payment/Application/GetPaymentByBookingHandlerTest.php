<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Application\Query\GetPaymentByBooking\GetPaymentByBookingHandler;
use App\Modules\Payment\Application\Query\GetPaymentByBooking\GetPaymentByBookingQuery;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PaymentStatus;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeBookingForQueryTest(): BookingId
{
    $user = bookingInsertUser('payment-query-'.uniqid().'@test.com');
    $categoryId = insertCategory('Beauty');
    $service = saveTimeSlotService('Haircut', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingIdStr = bookingInsertTimeSlotBooking(
        new UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );

    return new BookingId($bookingIdStr);
}

it('returns PaymentDTO for booking with payment', function (): void {
    $bookingId = makeBookingForQueryTest();
    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents(120000, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    app(PaymentRepositoryInterface::class)->save($payment);

    $handler = app(GetPaymentByBookingHandler::class);
    $dto = $handler->handle(new GetPaymentByBookingQuery($bookingId));

    expect($dto)->not->toBeNull()
        ->and($dto->id)->toBe($paymentId->toString())
        ->and($dto->bookingId)->toBe($bookingId->toString())
        ->and($dto->amountCents)->toBe(120000)
        ->and($dto->currency)->toBe('RUB')
        ->and($dto->status)->toBe(PaymentStatus::PENDING->value)
        ->and($dto->method)->toBe(PaymentMethod::CARD->value)
        ->and($dto->feePercent)->toBe(10)
        ->and($dto->platformFeeCents)->toBe(12000)
        ->and($dto->netAmountCents)->toBe(108000);
});

it('returns null when booking has no payment', function (): void {
    $bookingId = makeBookingForQueryTest();

    $handler = app(GetPaymentByBookingHandler::class);
    $result = $handler->handle(new GetPaymentByBookingQuery($bookingId));

    expect($result)->toBeNull();
});
