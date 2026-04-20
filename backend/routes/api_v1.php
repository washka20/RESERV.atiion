<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/**
 * REST API v1 — customer endpoints под JWT-гвардом.
 *
 * Routes модулей находятся автоматически через конвенцию
 * `app/Modules/*\/Interface/Api/routes.php`. Новый модуль положил routes.php —
 * подхватится без правок этого файла. Порядок детерминирован через sort.
 */
Route::prefix('v1')->group(function (): void {
    $moduleRouteFiles = glob(app_path('Modules/*/Interface/Api/routes.php')) ?: [];
    sort($moduleRouteFiles);

    foreach ($moduleRouteFiles as $routeFile) {
        require $routeFile;
    }
});
