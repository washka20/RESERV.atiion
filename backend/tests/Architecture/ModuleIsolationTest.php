<?php

declare(strict_types=1);

arch('Identity\\Domain изолирован от других модулей')
    ->expect('App\Modules\Identity\Domain')
    ->not->toUse([
        'App\Modules\Catalog',
        'App\Modules\Booking',
        'App\Modules\Payment',
    ]);

arch('Catalog\\Domain изолирован от других модулей')
    ->expect('App\Modules\Catalog\Domain')
    ->not->toUse([
        'App\Modules\Identity',
        'App\Modules\Booking',
        'App\Modules\Payment',
    ]);

arch('Booking\\Domain изолирован от других модулей')
    ->expect('App\Modules\Booking\Domain')
    ->not->toUse([
        'App\Modules\Identity',
        'App\Modules\Catalog',
        'App\Modules\Payment',
    ]);

arch('Payment\\Domain изолирован от других модулей')
    ->expect('App\Modules\Payment\Domain')
    ->not->toUse([
        'App\Modules\Identity',
        'App\Modules\Catalog',
        'App\Modules\Booking',
    ]);

arch('Application слой не тянет чужие Domain напрямую (Booking)')
    ->expect('App\Modules\Booking\Application')
    ->not->toUse([
        'App\Modules\Catalog\Domain',
        'App\Modules\Identity\Domain',
        'App\Modules\Payment\Domain',
    ]);
