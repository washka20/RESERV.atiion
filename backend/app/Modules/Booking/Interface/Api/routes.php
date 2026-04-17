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
    Route::post('bookings', [BookingController::class, 'store']);
    Route::patch('bookings/{id}/cancel', [BookingController::class, 'cancel'])->whereUuid('id');
});
