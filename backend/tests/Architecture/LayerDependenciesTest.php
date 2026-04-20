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

// ADR-016: DB::table допустим только в Query handlers (read-side).
// Command/Listener/Service — write-side, должны ходить через Repository интерфейсы.
// Защита от god-сервисов, которые обходят Domain слой через raw SQL.
arch('Command handlers не используют DB facade (Identity)')
    ->expect('App\Modules\Identity\Application\Command')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Command handlers не используют DB facade (Catalog)')
    ->expect('App\Modules\Catalog\Application\Command')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Command handlers не используют DB facade (Booking)')
    ->expect('App\Modules\Booking\Application\Command')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Command handlers не используют DB facade (Payment)')
    ->expect('App\Modules\Payment\Application\Command')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Application\Service не использует DB facade (Identity)')
    ->expect('App\Modules\Identity\Application\Service')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Application\Service не использует DB facade (Catalog)')
    ->expect('App\Modules\Catalog\Application\Service')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Application\Service не использует DB facade (Booking)')
    ->expect('App\Modules\Booking\Application\Service')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('Application\Service не использует DB facade (Payment)')
    ->expect('App\Modules\Payment\Application\Service')
    ->not->toUse('Illuminate\Support\Facades\DB');

// ADR-017: модули используют MediaStorageInterface, не конкретную S3MediaStorage.
// Защита от coupling к Laravel Storage и транспорта (S3 vs GCS vs локальный).
arch('Catalog не импортирует S3MediaStorage напрямую')
    ->expect('App\Modules\Catalog')
    ->not->toUse([
        'App\Shared\Infrastructure\Media\S3MediaStorage',
    ]);
