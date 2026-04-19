<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('booking→payment→payout e2e: 10% fee, confirmed, clean outbox', function (): void {
    $user = bookingInsertUser('flow-customer@test.com');
    $orgId = insertOrganizationForTests('flow-org');
    $categoryId = insertCategory('FlowCat');
    $service = saveTimeSlotService(
        name: 'Premium Haircut',
        categoryId: $categoryId,
        priceCents: 240_000,
        organizationId: $orgId,
    );
    $slotId = bookingInsertTimeSlot($service->id());

    $token = bookingIssueJwt($user);

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
        ->postJson('/api/v1/bookings', [
            'service_id' => $service->id()->toString(),
            'type' => 'time_slot',
            'slot_id' => $slotId,
        ]);

    $response->assertStatus(201);
    $bookingId = $response->json('data.id');

    // InitiatePaymentOnBookingCreated сработал синхронно → Payment initiate → charge (Null GW success)
    // → MarkPaymentPaid → PaymentReceived записано в outbox (reliable=true).
    // Прогоняем outbox worker чтобы dispatch PaymentReceived → ConfirmBooking + CreatePayoutTx.
    Artisan::call('app:outbox:work', ['--once' => true]);

    // ConfirmBookingCommand мог породить BookingConfirmed в outbox — прогон ещё раз, если нужно.
    Artisan::call('app:outbox:work', ['--once' => true]);

    $payment = DB::table('payments')->where('booking_id', $bookingId)->first();
    expect($payment)->not->toBeNull()
        ->and((int) $payment->amount_cents)->toBe(240_000)
        ->and($payment->currency)->toBe('RUB')
        ->and($payment->status)->toBe('paid')
        ->and((int) $payment->platform_fee_cents)->toBe(24_000)
        ->and((int) $payment->net_amount_cents)->toBe(216_000)
        ->and((int) $payment->marketplace_fee_percent)->toBe(10);

    $booking = DB::table('bookings')->where('id', $bookingId)->first();
    expect($booking->status)->toBe('confirmed');

    $payout = DB::table('payout_transactions')->where('booking_id', $bookingId)->first();
    expect($payout)->not->toBeNull()
        ->and($payout->organization_id)->toBe($orgId->toString())
        ->and((int) $payout->gross_amount_cents)->toBe(240_000)
        ->and((int) $payout->platform_fee_cents)->toBe(24_000)
        ->and((int) $payout->net_amount_cents)->toBe(216_000)
        ->and($payout->status)->toBe('pending');

    expect(DB::table('outbox_messages')->where('status', 'pending')->count())->toBe(0);
});
