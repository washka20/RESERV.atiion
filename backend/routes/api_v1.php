<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    require __DIR__.'/../app/Modules/Identity/Interface/Api/routes.php';
    require __DIR__.'/../app/Modules/Catalog/Interface/Api/routes.php';
    require __DIR__.'/../app/Modules/Booking/Interface/Api/routes.php';
    require __DIR__.'/../app/Modules/Payment/Interface/Api/routes.php';
});
