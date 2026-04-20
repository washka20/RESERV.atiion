<?php

declare(strict_types=1);

use App\Modules\Catalog\Interface\Api\Controller\CategoryController;
use App\Modules\Catalog\Interface\Api\Controller\ServiceController;
use App\Modules\Identity\Interface\Api\Middleware\JwtAuthMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{id}', [ServiceController::class, 'show'])->whereUuid('id');

Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{slug}', [CategoryController::class, 'show']);

// Org-scoped services — требует membership с правом services.edit.
Route::middleware([JwtAuthMiddleware::class, 'org.member:services.edit'])->group(function (): void {
    Route::get('organizations/{slug}/services', [ServiceController::class, 'indexForOrganization']);
});
