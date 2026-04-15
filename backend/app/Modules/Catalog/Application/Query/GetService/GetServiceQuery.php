<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Query\GetService;

/**
 * Запрос одной услуги по идентификатору.
 */
final readonly class GetServiceQuery
{
    public function __construct(
        public string $serviceId,
    ) {}
}
