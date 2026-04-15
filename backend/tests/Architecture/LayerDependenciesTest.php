<?php

declare(strict_types=1);

arch('Domain слой не импортирует Illuminate')
    ->expect('App\Modules')
    ->toOnlyBeUsedIn('App\Modules')
    ->ignoring(['App\Shared', 'App\Providers', 'Database\Seeders', 'Database\Factories']);

arch('Domain не использует Laravel фреймворк (Identity)')
    ->expect('App\Modules\Identity\Domain')
    ->not->toUse('Illuminate');

arch('Domain не использует Laravel фреймворк (Catalog)')
    ->expect('App\Modules\Catalog\Domain')
    ->not->toUse('Illuminate');

arch('Domain не использует Laravel фреймворк (Booking)')
    ->expect('App\Modules\Booking\Domain')
    ->not->toUse('Illuminate');

arch('Domain не использует Laravel фреймворк (Payment)')
    ->expect('App\Modules\Payment\Domain')
    ->not->toUse('Illuminate');

arch('Shared Domain не использует Illuminate')
    ->expect('App\Shared\Domain')
    ->not->toUse('Illuminate');

arch('Application не использует Eloquent (Identity)')
    ->expect('App\Modules\Identity\Application')
    ->not->toUse('Illuminate\Database\Eloquent');

arch('Application не использует Eloquent (Catalog)')
    ->expect('App\Modules\Catalog\Application')
    ->not->toUse('Illuminate\Database\Eloquent');

arch('Application не использует Eloquent (Booking)')
    ->expect('App\Modules\Booking\Application')
    ->not->toUse('Illuminate\Database\Eloquent');

arch('Application не использует Eloquent (Payment)')
    ->expect('App\Modules\Payment\Application')
    ->not->toUse('Illuminate\Database\Eloquent');
