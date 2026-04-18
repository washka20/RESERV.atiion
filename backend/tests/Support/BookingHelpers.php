<?php

declare(strict_types=1);

use App\Modules\Booking\Domain\ValueObject\BookingId;
use App\Modules\Booking\Domain\ValueObject\BookingStatus;
use App\Modules\Booking\Domain\ValueObject\BookingType;
use App\Modules\Booking\Domain\ValueObject\SlotId;
use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Application\Service\JwtTokenServiceInterface;
use App\Modules\Identity\Domain\ValueObject\UserId;
use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;

/*
 * Booking test helpers — глобальные функции для Feature-тестов.
 *
 * Подключается через composer.json autoload-dev "files" — гарантирует загрузку
 * функций в каждый процесс paratest.
 */

if (! function_exists('bookingInsertUser')) {
    /**
     * Создаёт пользователя напрямую в БД через фабрику.
     *
     * Для Booking-endpoints достаточно валидного UserModel — authZ
     * (ownership, admin-check) проверяется Application-слоем, а не Spatie.
     */
    function bookingInsertUser(string $email = 'booking-user@test.com'): UserModel
    {
        /** @var UserModel $user */
        $user = UserModel::factory()->create(['email' => $email]);

        return $user;
    }
}

if (! function_exists('bookingIssueJwt')) {
    /**
     * Выпускает валидный JWT access token для пользователя.
     *
     * Используется для установки Authorization: Bearer в Feature-тестах
     * защищённых JwtAuthMiddleware роутов.
     */
    function bookingIssueJwt(UserModel $user): string
    {
        $jwt = app(JwtTokenServiceInterface::class);
        $pair = $jwt->issue(new UserId((string) $user->getAuthIdentifier()));

        return $pair->accessToken;
    }
}

if (! function_exists('bookingInsertTimeSlot')) {
    /**
     * Inserts a time slot into DB and returns its UUID.
     *
     * $start / $end — relative strtotime strings (по умолчанию — через 2 дня).
     */
    function bookingInsertTimeSlot(
        ServiceId $serviceId,
        string $start = '+2 days 10:00',
        string $end = '+2 days 11:00',
        bool $isBooked = false,
        ?string $bookingId = null,
    ): string {
        $id = SlotId::generate()->toString();
        TimeSlotModel::query()->insert([
            'id' => $id,
            'service_id' => $serviceId->toString(),
            'start_at' => date('Y-m-d H:i:s', strtotime($start)),
            'end_at' => date('Y-m-d H:i:s', strtotime($end)),
            'is_booked' => $isBooked,
            'booking_id' => $bookingId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }
}

if (! function_exists('bookingInsertTimeSlotBooking')) {
    /**
     * Raw INSERT бронирования типа TIME_SLOT. Обходит domain фабрику, поэтому
     * можно создавать бронирования с прошедшим временем (для тестов cancellation window).
     *
     * @param  array<string, mixed>  $overrides
     */
    function bookingInsertTimeSlotBooking(
        UserId $userId,
        ServiceId $serviceId,
        string $slotId,
        string $startAt = '+2 days 10:00',
        string $endAt = '+2 days 11:00',
        array $overrides = [],
    ): string {
        $id = BookingId::generate()->toString();
        $defaults = [
            'id' => $id,
            'user_id' => $userId->toString(),
            'service_id' => $serviceId->toString(),
            'type' => BookingType::TIME_SLOT->value,
            'status' => BookingStatus::PENDING->value,
            'slot_id' => $slotId,
            'start_at' => date('Y-m-d H:i:s', strtotime($startAt)),
            'end_at' => date('Y-m-d H:i:s', strtotime($endAt)),
            'check_in' => null,
            'check_out' => null,
            'quantity' => null,
            'total_price_amount' => '1000.00',
            'total_price_currency' => 'RUB',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        BookingModel::query()->insert(array_merge($defaults, $overrides));

        return $id;
    }
}

if (! function_exists('bookingInsertQuantityBooking')) {
    /**
     * Raw INSERT бронирования типа QUANTITY.
     *
     * @param  array<string, mixed>  $overrides
     */
    function bookingInsertQuantityBooking(
        UserId $userId,
        ServiceId $serviceId,
        string $checkIn = '+2 days',
        string $checkOut = '+5 days',
        int $quantity = 1,
        array $overrides = [],
    ): string {
        $id = BookingId::generate()->toString();
        $defaults = [
            'id' => $id,
            'user_id' => $userId->toString(),
            'service_id' => $serviceId->toString(),
            'type' => BookingType::QUANTITY->value,
            'status' => BookingStatus::PENDING->value,
            'slot_id' => null,
            'start_at' => null,
            'end_at' => null,
            'check_in' => date('Y-m-d', strtotime($checkIn)),
            'check_out' => date('Y-m-d', strtotime($checkOut)),
            'quantity' => $quantity,
            'total_price_amount' => '1500.00',
            'total_price_currency' => 'RUB',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        BookingModel::query()->insert(array_merge($defaults, $overrides));

        return $id;
    }
}
