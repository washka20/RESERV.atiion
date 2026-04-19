<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Catalog\Application\Support;

use App\Modules\Catalog\Domain\Entity\Service;
use App\Modules\Catalog\Domain\ValueObject\CategoryId;
use App\Modules\Catalog\Domain\ValueObject\Duration;
use App\Modules\Catalog\Domain\ValueObject\Money;
use App\Modules\Catalog\Domain\ValueObject\ServiceId;
use App\Modules\Identity\Domain\ValueObject\OrganizationId;

/**
 * Утилита для тестов — возвращает активный Service типа TIME_SLOT без recorded events.
 */
final class ServiceFactory
{
    public static function timeSlot(
        ?ServiceId $id = null,
        ?CategoryId $categoryId = null,
        ?OrganizationId $organizationId = null,
    ): Service {
        $service = Service::createTimeSlot(
            $id ?? ServiceId::generate(),
            'Test service',
            'desc',
            Money::fromCents(100000, 'RUB'),
            Duration::ofMinutes(60),
            $categoryId ?? CategoryId::generate(),
            null,
            $organizationId ?? OrganizationId::generate(),
        );
        $service->pullDomainEvents();

        return $service;
    }
}
