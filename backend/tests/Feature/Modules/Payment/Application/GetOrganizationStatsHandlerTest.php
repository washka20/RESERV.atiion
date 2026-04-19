<?php

declare(strict_types=1);

use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Payment\Application\Query\GetOrganizationStats\GetOrganizationStatsHandler;
use App\Modules\Payment\Application\Query\GetOrganizationStats\GetOrganizationStatsQuery;
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

function seedPayoutForStats(OrganizationId $orgId, int $grossCents): void
{
    $user = bookingInsertUser('stats-'.uniqid().'@test.com');
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

it('aggregates revenue / fee / net / bookings over the last 30 days', function (): void {
    $orgId = insertOrganizationForTests('stats-org');
    seedPayoutForStats($orgId, 100_000);
    seedPayoutForStats($orgId, 200_000);

    $stats = app(GetOrganizationStatsHandler::class)
        ->handle(new GetOrganizationStatsQuery($orgId->toString()));

    expect($stats->revenue30dCents)->toBe(300_000)
        ->and($stats->platformFee30dCents)->toBe(30_000)
        ->and($stats->netPayout30dCents)->toBe(270_000)
        ->and($stats->bookings30d)->toBe(2)
        ->and($stats->conversionRate)->toBe(0.0);
});

it('returns empty stats for organization without activity', function (): void {
    $orgId = insertOrganizationForTests('empty-stats-org');

    $stats = app(GetOrganizationStatsHandler::class)
        ->handle(new GetOrganizationStatsQuery($orgId->toString()));

    expect($stats->revenue30dCents)->toBe(0)
        ->and($stats->bookings30d)->toBe(0)
        ->and($stats->topServices)->toBe([]);
});

it('returns top services sorted by bookings descending', function (): void {
    $orgId = insertOrganizationForTests('top-stats-org');
    seedPayoutForStats($orgId, 100_000);
    seedPayoutForStats($orgId, 200_000);

    $stats = app(GetOrganizationStatsHandler::class)
        ->handle(new GetOrganizationStatsQuery($orgId->toString()));

    expect($stats->topServices)->toBeArray()
        ->and(count($stats->topServices))->toBeGreaterThan(0)
        ->and($stats->topServices[0])->toHaveKeys(['id', 'title', 'bookings']);
});
