<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [BookingController::class, 'index'])->name('dashboard');

    Route::resource('/categories', CategoryController::class)->middleware(['can:show']);
    Route::get('/subcategories/{category}', [CategoryController::class, 'getSubcategories']);

    Route::resource('/products', ProductController::class)->middleware(['can:show']);
    Route::resource('/subcategories', SubcategoryController::class)->middleware(['can:show']);

    Route::resource('/roles', RoleController::class)->middleware('role:super-admin');
    Route::resource('/users', UserController::class)->middleware('role:super-admin');
});

require __DIR__.'/auth.php';
