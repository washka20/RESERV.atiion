<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\AddServiceImage;

final readonly class AddServiceImageCommand
{
    public function __construct(
        public string $serviceId,
        public string $imagePath,
    ) {}
}
