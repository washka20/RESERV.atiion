<?php

declare(strict_types=1);

use App\Modules\Catalog\Interface\Api\Controller\CategoryController;
use App\Modules\Catalog\Interface\Api\Controller\ServiceController;
use Illuminate\Support\Facades\Route;

Route::get('services', [ServiceController::class, 'index']);
Route::get('services/{id}', [ServiceController::class, 'show'])->whereUuid('id');

Route::get('categories', [CategoryController::class, 'index']);
Route::get('categories/{slug}', [CategoryController::class, 'show']);
