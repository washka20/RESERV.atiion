<?php

declare(strict_types=1);

use App\Modules\Booking\Interface\Api\Controller\AvailabilityController;
use App\Modules\Booking\Interface\Api\Controller\BookingController;
use App\Modules\Identity\Interface\Api\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(JwtAuthMiddleware::class)->group(function (): void {
    Route::get('services/{service}/availability', [AvailabilityController::class, 'check'])
        ->whereUuid('service');

    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/{id}', [BookingController::class, 'show'])->whereUuid('id');

    // Writes — named rate limiter (AppServiceProvider::boot + ADR-018).
    // В testing env лимиты сняты — параллельные тесты иначе забивают quotas.
    Route::post('bookings', [BookingController::class, 'store'])->middleware('throttle:booking-write');
    Route::patch('bookings/{id}/cancel', [BookingController::class, 'cancel'])
        ->whereUuid('id')
        ->middleware('throttle:booking-write');
});
