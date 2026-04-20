<?php

declare(strict_types=1);

use App\Modules\Identity\Interface\Api\Controller\AuthController;
use App\Modules\Identity\Interface\Api\Controller\MeController;
use App\Modules\Identity\Interface\Api\Controller\MembershipController;
use App\Modules\Identity\Interface\Api\Controller\OrganizationController;
use App\Modules\Identity\Interface\Api\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    // Rate limits — anti-bruteforce + anti-enumeration (ADR-018).
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:3,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:20,1');

    Route::middleware(JwtAuthMiddleware::class)->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::put('me', [AuthController::class, 'updateMe'])->middleware('throttle:10,1');
    });
});

Route::middleware(JwtAuthMiddleware::class)->group(function (): void {
    Route::get('me/memberships', [MeController::class, 'memberships']);

    Route::post('organizations', [OrganizationController::class, 'store']);
    Route::get('organizations/{slug}', [OrganizationController::class, 'show']);

    Route::middleware('org.member:settings.manage')->group(function (): void {
        Route::patch('organizations/{slug}', [OrganizationController::class, 'update']);
    });
    Route::middleware('org.member:organization.archive')->group(function (): void {
        Route::delete('organizations/{slug}', [OrganizationController::class, 'archive']);
    });

    Route::middleware('org.member:team.view')->group(function (): void {
        Route::get('organizations/{slug}/members', [MembershipController::class, 'index']);
    });
    Route::middleware('org.member:team.manage')->group(function (): void {
        Route::post('organizations/{slug}/members/invite', [MembershipController::class, 'invite']);
        Route::delete('organizations/{slug}/members/{membershipId}', [MembershipController::class, 'revoke'])
            ->whereUuid('membershipId');
        Route::patch('organizations/{slug}/members/{membershipId}/role', [MembershipController::class, 'changeRole'])
            ->whereUuid('membershipId');
    });
});
