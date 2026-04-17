<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function listAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.bookingIssueJwt($user)];
}

it('returns only own bookings — other users are not visible', function (): void {
    $alice = bookingInsertUser('alice@test.com');
    $bob = bookingInsertUser('bob@test.com');

    $categoryId = insertCategory('ListIsolation');
    $service = saveTimeSlotService('Service', $categoryId);

    $aliceSlot1 = bookingInsertTimeSlot($service->id(), start: '+2 days 10:00', end: '+2 days 11:00');
    $aliceBookingId = bookingInsertTimeSlotBooking(
        new UserId($alice->id),
        $service->id(),
        $aliceSlot1,
        startAt: '+2 days 10:00',
        endAt: '+2 days 11:00',
    );

    $bobSlot = bookingInsertTimeSlot($service->id(), start: '+3 days 10:00', end: '+3 days 11:00');
    $bobBookingId = bookingInsertTimeSlotBooking(
        new UserId($bob->id),
        $service->id(),
        $bobSlot,
        startAt: '+3 days 10:00',
        endAt: '+3 days 11:00',
    );

    $response = $this->withHeaders(listAuthHeader($alice))
        ->getJson('/api/v1/bookings');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'error' => null,
        ])
        ->assertJsonStructure([
            'success',
            'data',
            'error',
            'meta' => ['page', 'per_page', 'total', 'last_page'],
        ]);

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($aliceBookingId);
    expect($ids)->not->toContain($bobBookingId);
    expect($response->json('meta.total'))->toBe(1);
});

it('filters own bookings by status=pending', function (): void {
    $user = bookingInsertUser('list-status@test.com');
    $categoryId = insertCategory('ListStatus');
    $service = saveTimeSlotService('Service', $categoryId);

    $pendingSlot = bookingInsertTimeSlot($service->id(), start: '+2 days 10:00', end: '+2 days 11:00');
    $pendingId = bookingInsertTimeSlotBooking(
        new UserId($user->id),
        $service->id(),
        $pendingSlot,
        startAt: '+2 days 10:00',
        endAt: '+2 days 11:00',
    );

    $cancelledSlot = bookingInsertTimeSlot($service->id(), start: '+3 days 10:00', end: '+3 days 11:00');
    $cancelledId = bookingInsertTimeSlotBooking(
        new UserId($user->id),
        $service->id(),
        $cancelledSlot,
        startAt: '+3 days 10:00',
        endAt: '+3 days 11:00',
        overrides: ['status' => BookingStatus::CANCELLED->value],
    );

    $response = $this->withHeaders(listAuthHeader($user))
        ->getJson('/api/v1/bookings?status=pending');

    $response->assertStatus(200);
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($pendingId);
    expect($ids)->not->toContain($cancelledId);
    expect($response->json('meta.total'))->toBe(1);
});

it('paginates with page and per_page', function (): void {
    $user = bookingInsertUser('list-page@test.com');
    $categoryId = insertCategory('ListPage');
    $service = saveTimeSlotService('Service', $categoryId);

    for ($i = 1; $i <= 5; $i++) {
        $slotId = bookingInsertTimeSlot(
            $service->id(),
            start: "+{$i} days 10:00",
            end: "+{$i} days 11:00",
        );
        bookingInsertTimeSlotBooking(
            new UserId($user->id),
            $service->id(),
            $slotId,
            startAt: "+{$i} days 10:00",
            endAt: "+{$i} days 11:00",
        );
    }

    $response = $this->withHeaders(listAuthHeader($user))
        ->getJson('/api/v1/bookings?page=2&per_page=2');

    $response->assertStatus(200)
        ->assertJson([
            'meta' => [
                'page' => 2,
                'per_page' => 2,
                'total' => 5,
                'last_page' => 3,
            ],
        ]);
    expect($response->json('data'))->toHaveCount(2);
});

it('returns 401 without Authorization header', function (): void {
    $response = $this->getJson('/api/v1/bookings');

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'NO_TOKEN']]);
});
