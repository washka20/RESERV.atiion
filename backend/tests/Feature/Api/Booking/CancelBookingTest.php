<?php

declare(strict_types=1);

use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function cancelAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.bookingIssueJwt($user)];
}

it('cancels own TIME_SLOT booking — status cancelled + slot released', function (): void {
    $user = bookingInsertUser('cancel-own@test.com');
    $categoryId = insertCategory('CancelOwn');
    $service = saveTimeSlotService('Haircut', $categoryId);

    $slotId = bookingInsertTimeSlot(
        $service->id(),
        start: '+3 days 10:00',
        end: '+3 days 11:00',
        isBooked: false,
    );
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId($user->id),
        $service->id(),
        $slotId,
        startAt: '+3 days 10:00',
        endAt: '+3 days 11:00',
    );
    TimeSlotModel::query()->where('id', $slotId)
        ->update(['is_booked' => true, 'booking_id' => $bookingId]);

    $response = $this->withHeaders(cancelAuthHeader($user))
        ->patchJson("/api/v1/bookings/{$bookingId}/cancel");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => $bookingId,
                'status' => 'cancelled',
            ],
        ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'status' => 'cancelled',
    ]);
    $this->assertDatabaseHas('time_slots', [
        'id' => $slotId,
        'is_booked' => false,
        'booking_id' => null,
    ]);
});

it('cancels a QUANTITY booking without touching any time slot', function (): void {
    $user = bookingInsertUser('cancel-q@test.com');
    $categoryId = insertCategory('CancelQ');

    $entity = Service::createQuantity(
        ServiceId::generate(),
        'Apartment',
        'desc',
        Money::fromCents(500000, 'RUB'),
        10,
        $categoryId,
        null,
        insertOrganizationForTests(),
    );
    app(ServiceRepositoryInterface::class)->save($entity);

    $bookingId = bookingInsertQuantityBooking(
        new UserId($user->id),
        $entity->id(),
        checkIn: '+3 days',
        checkOut: '+5 days',
        quantity: 2,
    );

    $slotsBefore = TimeSlotModel::query()->count();

    $response = $this->withHeaders(cancelAuthHeader($user))
        ->patchJson("/api/v1/bookings/{$bookingId}/cancel");

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => ['status' => 'cancelled'],
        ]);
    $this->assertDatabaseHas('bookings', ['id' => $bookingId, 'status' => 'cancelled']);
    expect(TimeSlotModel::query()->count())->toBe($slotsBefore);
});

it('returns 403 when attempting to cancel someone else booking', function (): void {
    $owner = bookingInsertUser('cancel-owner@test.com');
    $intruder = bookingInsertUser('cancel-intruder@test.com');

    $categoryId = insertCategory('CancelForbidden');
    $service = saveTimeSlotService('Haircut', $categoryId);

    $slotId = bookingInsertTimeSlot($service->id(), start: '+3 days 10:00', end: '+3 days 11:00');
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId($owner->id),
        $service->id(),
        $slotId,
        startAt: '+3 days 10:00',
        endAt: '+3 days 11:00',
    );

    $response = $this->withHeaders(cancelAuthHeader($intruder))
        ->patchJson("/api/v1/bookings/{$bookingId}/cancel");

    $response->assertStatus(403)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'FORBIDDEN'],
        ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'status' => 'pending',
    ]);
});

it('returns 409 BOOKING_CANCELLATION_NOT_ALLOWED when outside cancellation window', function (): void {
    $user = bookingInsertUser('cancel-late@test.com');
    $categoryId = insertCategory('CancelLate');
    $service = saveTimeSlotService('Haircut', $categoryId);

    // Booking start_at в ближайший час — вне окна отмены (минимум 24ч)
    // Обходим domain factory (она запрещает past/near-future) через прямой INSERT
    $slotId = bookingInsertTimeSlot(
        $service->id(),
        start: '+1 hour',
        end: '+2 hours',
        isBooked: false,
    );
    $bookingId = bookingInsertTimeSlotBooking(
        new UserId($user->id),
        $service->id(),
        $slotId,
        startAt: '+1 hour',
        endAt: '+2 hours',
    );

    $response = $this->withHeaders(cancelAuthHeader($user))
        ->patchJson("/api/v1/bookings/{$bookingId}/cancel");

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'BOOKING_CANCELLATION_NOT_ALLOWED'],
        ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'status' => 'pending',
    ]);
});

it('returns 404 when booking is not found', function (): void {
    $user = bookingInsertUser('cancel-404@test.com');

    $randomId = (string) Str::uuid();
    $response = $this->withHeaders(cancelAuthHeader($user))
        ->patchJson("/api/v1/bookings/{$randomId}/cancel");

    $response->assertStatus(404)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'BOOKING_NOT_FOUND'],
        ]);
});
