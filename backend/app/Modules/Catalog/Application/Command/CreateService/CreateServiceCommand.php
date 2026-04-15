<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateService;

/**
 * Команда создания услуги. type = 'time_slot' требует durationMinutes,
 * 'quantity' — totalQuantity.
 */
final readonly class CreateServiceCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public int $priceAmount,
        public string $priceCurrency,
        public string $type,
        public string $categoryId,
        public ?string $subcategoryId = null,
        public ?int $durationMinutes = null,
        public ?int $totalQuantity = null,
    ) {}
}
