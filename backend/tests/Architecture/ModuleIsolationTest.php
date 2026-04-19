<?php

declare(strict_types=1);

/*
 * Цель: Domain слой каждого модуля не тянет Infrastructure/Application/Interface другого модуля.
 *
 * Допустимо: Domain ↔ чужой Domain (public VOs, identifiers, repository interfaces) —
 * это каноничный DDD-паттерн shared kernel / cross-BC references by ID.
 * Booking\Domain опирается на Catalog\Domain (Money, ServiceId, ServiceType, Service entity, ServiceRepositoryInterface)
 * и Identity\Domain (UserId) — согласно спеке Plan 7.
 */

arch('Identity\\Domain изолирован от Infrastructure/Application/Interface других модулей')
    ->expect('App\Modules\Identity\Domain')
    ->not->toUse([
        'App\Modules\Catalog\Infrastructure',
        'App\Modules\Catalog\Application',
        'App\Modules\Catalog\Interface',
        'App\Modules\Booking\Infrastructure',
        'App\Modules\Booking\Application',
        'App\Modules\Booking\Interface',
        'App\Modules\Payment\Infrastructure',
        'App\Modules\Payment\Application',
        'App\Modules\Payment\Interface',
    ]);

arch('Catalog\\Domain изолирован от Infrastructure/Application/Interface других модулей')
    ->expect('App\Modules\Catalog\Domain')
    ->not->toUse([
        'App\Modules\Identity\Infrastructure',
        'App\Modules\Identity\Application',
        'App\Modules\Identity\Interface',
        'App\Modules\Booking\Infrastructure',
        'App\Modules\Booking\Application',
        'App\Modules\Booking\Interface',
        'App\Modules\Payment\Infrastructure',
        'App\Modules\Payment\Application',
        'App\Modules\Payment\Interface',
    ]);

arch('Booking\\Domain изолирован от Infrastructure/Application/Interface других модулей')
    ->expect('App\Modules\Booking\Domain')
    ->not->toUse([
        'App\Modules\Identity\Infrastructure',
        'App\Modules\Identity\Application',
        'App\Modules\Identity\Interface',
        'App\Modules\Catalog\Infrastructure',
        'App\Modules\Catalog\Application',
        'App\Modules\Catalog\Interface',
        'App\Modules\Payment\Infrastructure',
        'App\Modules\Payment\Application',
        'App\Modules\Payment\Interface',
    ]);

arch('Payment\\Domain изолирован от Infrastructure/Application/Interface других модулей')
    ->expect('App\Modules\Payment\Domain')
    ->not->toUse([
        'App\Modules\Identity\Infrastructure',
        'App\Modules\Identity\Application',
        'App\Modules\Identity\Interface',
        'App\Modules\Catalog\Infrastructure',
        'App\Modules\Catalog\Application',
        'App\Modules\Catalog\Interface',
        'App\Modules\Booking\Infrastructure',
        'App\Modules\Booking\Application',
        'App\Modules\Booking\Interface',
    ]);

/*
 * Application слой модуля НЕ тянет чужие Infrastructure/Application/Interface.
 * Ссылки на чужие Domain (Entity/ValueObject/Repository interfaces) допустимы —
 * например, Booking\Application\Command\CreateBookingHandler использует
 * Catalog\Domain\Repository\ServiceRepositoryInterface.
 */
arch('Booking\\Application не зависит от чужих Infrastructure/Application/Interface')
    ->expect('App\Modules\Booking\Application')
    ->not->toUse([
        'App\Modules\Identity\Infrastructure',
        'App\Modules\Identity\Application',
        'App\Modules\Identity\Interface',
        'App\Modules\Catalog\Infrastructure',
        'App\Modules\Catalog\Application',
        'App\Modules\Catalog\Interface',
        'App\Modules\Payment\Infrastructure',
        'App\Modules\Payment\Application',
        'App\Modules\Payment\Interface',
    ]);

/*
 * Payment\Application (Command/Query/Handler) не должен зависеть от чужих Application/Infrastructure/Interface.
 * Исключение — Listener подпакет: слушатели чужих Domain Events диспатчат Command/Query других BC
 * через Bus, и сами Command/Query DTO — это публичный контракт BC (как интерфейс).
 * Для Listener'ов проверяем только Infrastructure/Interface (строго запрещено).
 */
arch('Payment\\Application (Command/Query/Handler/Service/DTO) не зависит от чужих Infrastructure/Application/Interface')
    ->expect([
        'App\Modules\Payment\Application\Command',
        'App\Modules\Payment\Application\Query',
        'App\Modules\Payment\Application\Service',
        'App\Modules\Payment\Application\DTO',
    ])
    ->not->toUse([
        'App\Modules\Identity\Infrastructure',
        'App\Modules\Identity\Application',
        'App\Modules\Identity\Interface',
        'App\Modules\Catalog\Infrastructure',
        'App\Modules\Catalog\Application',
        'App\Modules\Catalog\Interface',
        'App\Modules\Booking\Infrastructure',
        'App\Modules\Booking\Application',
        'App\Modules\Booking\Interface',
    ]);

arch('Payment\\Application\\Listener не лезет в чужие Infrastructure/Interface')
    ->expect('App\Modules\Payment\Application\Listener')
    ->not->toUse([
        'App\Modules\Identity\Infrastructure',
        'App\Modules\Identity\Interface',
        'App\Modules\Catalog\Infrastructure',
        'App\Modules\Catalog\Interface',
        'App\Modules\Booking\Infrastructure',
        'App\Modules\Booking\Interface',
    ]);
