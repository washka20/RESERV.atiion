<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\ModuleServiceProvider;
use App\Shared\Infrastructure\Media\MediaServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    ModuleServiceProvider::class,
    MediaServiceProvider::class,
];
