<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\UpdateService;

final readonly class UpdateServiceCommand
{
    public function __construct(
        public string $serviceId,
        public string $name,
        public string $description,
        public int $priceAmount,
        public string $priceCurrency,
    ) {}
}
