<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Booking policies
    |--------------------------------------------------------------------------
    |
    | Минимальное и максимальное время до начала бронирования; лимит активных
    | бронирований на пользователя; минимальное время до начала для отмены.
    */

    'min_advance_minutes' => (int) env('BOOKING_MIN_ADVANCE_MINUTES', 60),

    'max_advance_days' => (int) env('BOOKING_MAX_ADVANCE_DAYS', 365),

    'min_cancellation_hours_before' => (int) env('BOOKING_MIN_CANCELLATION_HOURS_BEFORE', 24),

    'user_active_limit' => (int) env('BOOKING_USER_ACTIVE_LIMIT', 10),
];
