<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ModuleServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ModuleServiceProvider::class,
];
