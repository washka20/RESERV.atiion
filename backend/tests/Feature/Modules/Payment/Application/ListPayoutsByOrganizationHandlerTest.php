<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Application\Query\ListPayoutsByOrganization\ListPayoutsByOrganizationHandler;
use App\Modules\Payment\Application\Query\ListPayoutsByOrganization\ListPayoutsByOrganizationQuery;
use App\Modules\Payment\Domain\Entity\Payment;
use App\Modules\Payment\Domain\Entity\PayoutTransaction;
use App\Modules\Payment\Domain\Repository\PaymentRepositoryInterface;
use App\Modules\Payment\Domain\Repository\PayoutTransactionRepositoryInterface;
use App\Modules\Payment\Domain\ValueObject\PaymentId;
use App\Modules\Payment\Domain\ValueObject\PaymentMethod;
use App\Modules\Payment\Domain\ValueObject\PayoutTransactionId;
use App\Modules\Payment\Domain\ValueObject\Percentage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function seedPayoutForList(OrganizationId $orgId, int $grossCents = 100_000): void
{
    $user = bookingInsertUser('list-payout-'.uniqid().'@test.com');
    $categoryId = insertCategory('Beauty-'.uniqid());
    $service = saveTimeSlotService('Haircut', $categoryId, organizationId: $orgId);
    $slotId = bookingInsertTimeSlot($service->id());
    $bookingIdStr = bookingInsertTimeSlotBooking(
        new UserId((string) $user->getAuthIdentifier()),
        $service->id(),
        $slotId,
    );
    $bookingId = new \App\Modules\Booking\Domain\ValueObject\BookingId($bookingIdStr);

    $paymentId = PaymentId::generate();
    $payment = Payment::initiate(
        $paymentId,
        $bookingId,
        Money::fromCents($grossCents, 'RUB'),
        PaymentMethod::CARD,
        Percentage::fromInt(10),
    );
    app(PaymentRepositoryInterface::class)->save($payment);

    $feeCents = (int) round($grossCents * 10 / 100);
    $netCents = $grossCents - $feeCents;

    $payout = PayoutTransaction::create(
        PayoutTransactionId::generate(),
        $bookingId,
        $orgId,
        $paymentId,
        Money::fromCents($grossCents),
        Money::fromCents($feeCents),
        Money::fromCents($netCents),
    );
    app(PayoutTransactionRepositoryInterface::class)->save($payout);
}

it('lists payouts for organization with pagination meta', function (): void {
    $orgId = insertOrganizationForTests('list-payout-org');
    seedPayoutForList($orgId, 100_000);
    seedPayoutForList($orgId, 200_000);
    seedPayoutForList($orgId, 300_000);

    $result = app(ListPayoutsByOrganizationHandler::class)
        ->handle(new ListPayoutsByOrganizationQuery($orgId->toString(), page: 1, perPage: 2));

    expect($result['items'])->toHaveCount(2)
        ->and($result['meta']['total'])->toBe(3)
        ->and($result['meta']['page'])->toBe(1)
        ->and($result['meta']['per_page'])->toBe(2)
        ->and($result['meta']['last_page'])->toBe(2);
});

it('returns empty list when organization has no payouts', function (): void {
    $orgId = insertOrganizationForTests('empty-payout-org');

    $result = app(ListPayoutsByOrganizationHandler::class)
        ->handle(new ListPayoutsByOrganizationQuery($orgId->toString()));

    expect($result['items'])->toBe([])
        ->and($result['meta']['total'])->toBe(0)
        ->and($result['meta']['last_page'])->toBe(1);
});
