<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\Repository\TimeSlotRepositoryInterface;
use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\Repository\ServiceRepositoryInterface;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * Concurrency-контракты Booking — против реального PostgreSQL.
 *
 * Ключевые защиты:
 * 1. EloquentTimeSlotRepository::markAsBooked — атомарный UPDATE ... WHERE is_booked=false.
 *    Conditional UPDATE атомарен для одной строки; второй вызов с тем же slot_id
 *    после успешного первого затронет 0 строк и вернёт false.
 * 2. EloquentBookingRepository::sumActiveQuantityOverlapping(lockForUpdate: true) —
 *    держит row-lock на активных booking'ах в диапазоне до commit транзакции.
 *
 * Тесты проверяют контракты sequential-вызовами в одном соединении. Полноценный stress-тест
 * с параллельными процессами (pcntl_fork или отдельные HTTP-воркеры) — отдельная работа
 * в рамках performance/load-тестирования, не в unit/feature сьюте.
 */

it('markAsBooked is atomic — subsequent calls on the same slot return false', function (): void {
    $catId = insertCategory('Beauty');
    $service = saveTimeSlotService('Haircut', $catId);

    $slotId = SlotId::generate();
    TimeSlotModel::query()->insert([
        'id' => $slotId->toString(),
        'service_id' => $service->id()->toString(),
        'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'end_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        'is_booked' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $bookingAId = BookingId::generate();
    $bookingBId = BookingId::generate();

    BookingModel::query()->insert([
        'id' => $bookingAId->toString(),
        'user_id' => bookingInsertUser('a@test.com')->getAuthIdentifier(),
        'service_id' => $service->id()->toString(),
        'type' => 'time_slot',
        'status' => 'pending',
        'slot_id' => $slotId->toString(),
        'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'end_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        'total_price_amount' => '1000.00',
        'total_price_currency' => 'RUB',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    BookingModel::query()->insert([
        'id' => $bookingBId->toString(),
        'user_id' => bookingInsertUser('b@test.com')->getAuthIdentifier(),
        'service_id' => $service->id()->toString(),
        'type' => 'time_slot',
        'status' => 'pending',
        'slot_id' => $slotId->toString(),
        'start_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
        'end_at' => now()->addDays(2)->addHour()->format('Y-m-d H:i:s'),
        'total_price_amount' => '1000.00',
        'total_price_currency' => 'RUB',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    /** @var TimeSlotRepositoryInterface $repo */
    $repo = app(TimeSlotRepositoryInterface::class);

    $first = $repo->markAsBooked($slotId, $bookingAId);
    $second = $repo->markAsBooked($slotId, $bookingBId);

    expect($first)->toBeTrue();
    expect($second)->toBeFalse();

    $slot = TimeSlotModel::query()->find($slotId->toString());
    expect((bool) $slot->is_booked)->toBeTrue();
    expect($slot->booking_id)->toBe($bookingAId->toString());
});

it('quantity over-booking blocked via API — insufficient capacity returns 409', function (): void {
    $catId = insertCategory('Rentals');
    $service = Service::createQuantity(
        ServiceId::generate(),
        'Apartment',
        'cozy',
        Money::fromCents(500000, 'RUB'),
        2,
        $catId,
        null,
        insertOrganizationForTests(),
    );
    app(ServiceRepositoryInterface::class)->save($service);
    $serviceId = $service->id();

    $owner = bookingInsertUser('owner@test.com');
    $ownerDomainId = new UserId((string) $owner->getAuthIdentifier());

    // полная ёмкость уже забронирована на пересекающийся диапазон
    bookingInsertQuantityBooking($ownerDomainId, $serviceId, '+3 days', '+5 days', quantity: 2);

    $requester = bookingInsertUser('requester@test.com');
    $token = bookingIssueJwt($requester);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/bookings', [
            'service_id' => $serviceId->toString(),
            'type' => 'quantity',
            'check_in' => now()->addDays(4)->format('Y-m-d'),
            'check_out' => now()->addDays(6)->format('Y-m-d'),
            'quantity' => 1,
        ]);

    $response->assertStatus(409)
        ->assertJsonPath('error.code', 'BOOKING_INSUFFICIENT_QUANTITY');

    expect(BookingModel::query()->where('service_id', $serviceId->toString())->count())->toBe(1);
});
