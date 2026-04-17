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

function createAuthHeader(UserModel $user): array
{
    return ['Authorization' => 'Bearer '.bookingIssueJwt($user)];
}

it('creates a TIME_SLOT booking — 201, DB row, slot marked booked', function (): void {
    $user = bookingInsertUser('create-ts@test.com');
    $categoryId = insertCategory('CreateTS');
    $service = saveTimeSlotService('Haircut', $categoryId);
    $slotId = bookingInsertTimeSlot($service->id());

    $response = $this->withHeaders(createAuthHeader($user))
        ->postJson('/api/v1/bookings', [
            'service_id' => $service->id()->toString(),
            'type' => 'time_slot',
            'slot_id' => $slotId,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'error' => null,
            'data' => [
                'type' => 'time_slot',
                'status' => 'pending',
                'service_id' => $service->id()->toString(),
                'slot_id' => $slotId,
            ],
        ]);

    $bookingId = $response->json('data.id');
    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'user_id' => $user->id,
        'type' => 'time_slot',
        'status' => 'pending',
        'slot_id' => $slotId,
    ]);
    $this->assertDatabaseHas('time_slots', [
        'id' => $slotId,
        'is_booked' => true,
        'booking_id' => $bookingId,
    ]);
});

it('returns 409 BOOKING_SLOT_UNAVAILABLE when slot is already booked', function (): void {
    $owner = bookingInsertUser('slot-owner@test.com');
    $secondUser = bookingInsertUser('second-user@test.com');

    $categoryId = insertCategory('AlreadyBooked');
    $service = saveTimeSlotService('Массаж', $categoryId);

    $slotId = bookingInsertTimeSlot(
        $service->id(),
        isBooked: false,
    );
    $priorBookingId = bookingInsertTimeSlotBooking(
        new UserId($owner->id),
        $service->id(),
        $slotId,
    );
    TimeSlotModel::query()
        ->where('id', $slotId)
        ->update(['is_booked' => true, 'booking_id' => $priorBookingId]);

    $response = $this->withHeaders(createAuthHeader($secondUser))
        ->postJson('/api/v1/bookings', [
            'service_id' => $service->id()->toString(),
            'type' => 'time_slot',
            'slot_id' => $slotId,
        ]);

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'BOOKING_SLOT_UNAVAILABLE'],
        ]);
});

it('creates a QUANTITY booking — 201, DB row present', function (): void {
    $user = bookingInsertUser('create-q@test.com');
    $categoryId = insertCategory('CreateQ');

    $entity = Service::createQuantity(
        ServiceId::generate(),
        'Apartment',
        'desc',
        Money::fromCents(500000, 'RUB'),
        10,
        $categoryId,
        null,
    );
    app(ServiceRepositoryInterface::class)->save($entity);

    $checkIn = date('Y-m-d', strtotime('+2 days'));
    $checkOut = date('Y-m-d', strtotime('+5 days'));

    $response = $this->withHeaders(createAuthHeader($user))
        ->postJson('/api/v1/bookings', [
            'service_id' => $entity->id()->toString(),
            'type' => 'quantity',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'quantity' => 2,
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'type' => 'quantity',
                'status' => 'pending',
                'quantity' => 2,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
            ],
        ]);

    $this->assertDatabaseHas('bookings', [
        'id' => $response->json('data.id'),
        'user_id' => $user->id,
        'type' => 'quantity',
        'status' => 'pending',
        'quantity' => 2,
    ]);
});

it('returns 409 BOOKING_INSUFFICIENT_QUANTITY when capacity is consumed', function (): void {
    $owner = bookingInsertUser('q-owner@test.com');
    $requester = bookingInsertUser('q-requester@test.com');

    $categoryId = insertCategory('QFull');
    $entity = Service::createQuantity(
        ServiceId::generate(),
        'Full hotel',
        'desc',
        Money::fromCents(300000, 'RUB'),
        3,
        $categoryId,
        null,
    );
    app(ServiceRepositoryInterface::class)->save($entity);

    $checkIn = '+2 days';
    $checkOut = '+5 days';

    bookingInsertQuantityBooking(
        new UserId($owner->id),
        $entity->id(),
        $checkIn,
        $checkOut,
        quantity: 3,
    );

    $response = $this->withHeaders(createAuthHeader($requester))
        ->postJson('/api/v1/bookings', [
            'service_id' => $entity->id()->toString(),
            'type' => 'quantity',
            'check_in' => date('Y-m-d', strtotime($checkIn)),
            'check_out' => date('Y-m-d', strtotime($checkOut)),
            'quantity' => 1,
        ]);

    $response->assertStatus(409)
        ->assertJson([
            'success' => false,
            'error' => ['code' => 'BOOKING_INSUFFICIENT_QUANTITY'],
        ]);
});

it('returns 422 on empty body', function (): void {
    $user = bookingInsertUser('create-empty@test.com');

    $response = $this->withHeaders(createAuthHeader($user))
        ->postJson('/api/v1/bookings', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['service_id', 'type']);
});

it('returns 401 without auth', function (): void {
    $response = $this->postJson('/api/v1/bookings', [
        'service_id' => (string) Str::uuid(),
        'type' => 'time_slot',
        'slot_id' => (string) Str::uuid(),
    ]);

    $response->assertStatus(401)
        ->assertJson(['success' => false, 'error' => ['code' => 'NO_TOKEN']]);
});
