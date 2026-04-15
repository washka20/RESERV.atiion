<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\ActivateService;

final readonly class ActivateServiceCommand
{
    public function __construct(public string $serviceId) {}
}
