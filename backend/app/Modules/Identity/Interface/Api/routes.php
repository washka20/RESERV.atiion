<?php

declare(strict_types=1);

use App\Modules\Identity\Interface\Api\Controller\AuthController;
use App\Modules\Identity\Interface\Api\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware(JwtAuthMiddleware::class)->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
