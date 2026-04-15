<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\DeactivateService;

final readonly class DeactivateServiceCommand
{
    public function __construct(public string $serviceId) {}
}
