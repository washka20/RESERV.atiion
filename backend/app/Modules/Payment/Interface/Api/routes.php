<?php

declare(strict_types=1);

use App\Modules\Identity\Interface\Api\Middleware\JwtAuthMiddleware;
use App\Modules\Payment\Interface\Api\Controller\PayoutController;
use Illuminate\Support\Facades\Route;

/*
 * Public Customer API для owner/member organization: выплаты, настройки, статистика.
 *
 * Все endpoints требуют JWT + membership в организации указанного slug. Гранулярные
 * permissions (payouts.view / payouts.manage / analytics.view) — через
 * MembershipGuardMiddleware (alias `org.member`).
 */
Route::middleware(JwtAuthMiddleware::class)
    ->prefix('organizations/{slug}')
    ->group(function (): void {
        Route::middleware('org.member:payouts.view')->group(function (): void {
            Route::get('payouts', [PayoutController::class, 'index']);
            Route::get('payout-settings', [PayoutController::class, 'settingsShow']);
        });

        Route::middleware('org.member:payouts.manage')->group(function (): void {
            Route::put('payout-settings', [PayoutController::class, 'settingsUpdate']);
        });

        Route::middleware('org.member:analytics.view')->group(function (): void {
            Route::get('stats', [PayoutController::class, 'stats']);
        });
    });
