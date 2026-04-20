<?php

declare(strict_types=1);

use App\Modules\Platform\Interface\Api\HealthController;
use Illuminate\Support\Facades\Route;

/**
 * Public Platform routes — load balancer/monitoring без auth.
 */
Route::get('health', HealthController::class);
