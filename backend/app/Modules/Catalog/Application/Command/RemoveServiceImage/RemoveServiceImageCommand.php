<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\RemoveServiceImage;

final readonly class RemoveServiceImageCommand
{
    public function __construct(
        public string $serviceId,
        public string $imagePath,
    ) {}
}
