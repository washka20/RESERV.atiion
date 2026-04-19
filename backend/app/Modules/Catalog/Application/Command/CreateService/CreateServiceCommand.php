<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Application\Command\CreateService;

/**
 * Команда создания услуги. type = 'time_slot' требует durationMinutes,
 * 'quantity' — totalQuantity.
 *
 * $organizationId — UUID организации-владельца. Actor должен быть member'ом
 * с permission services.create (проверяется middleware / admin UI).
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
        public string $organizationId,
        public ?string $subcategoryId = null,
        public ?int $durationMinutes = null,
        public ?int $totalQuantity = null,
    ) {}
}
