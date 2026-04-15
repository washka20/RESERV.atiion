<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Domain\ValueObject;

/**
 * Тип услуги — определяет модель букинга.
 *
 * TIME_SLOT: конкретный временной слот (парикмахер, массаж) — требует длительность.
 * QUANTITY:  кол-во единиц на диапазон дат (аренда оборудования, столики) — требует total quantity.
 */
enum ServiceType: string
{
    case TIME_SLOT = 'time_slot';
    case QUANTITY = 'quantity';

    /**
     * Требует ли этот тип услуги поле duration.
     */
    public function requiresDuration(): bool
    {
        return $this === self::TIME_SLOT;
    }

    /**
     * Требует ли этот тип услуги поле total_quantity.
     */
    public function requiresTotalQuantity(): bool
    {
        return $this === self::QUANTITY;
    }
}
