<?php

declare(strict_types=1);

namespace App\Modules\Identity\Domain\ValueObject;

/**
 * Тип организации — определяет категорию бизнеса.
 *
 * SALON — парикмахерские, салоны красоты, барбершопы
 * RENTAL — аренда (апартаменты, автомобили, оборудование)
 * CONSULT — консультации, медицина, юристы, репетиторы
 * OTHER — прочее (впишется позже, добавлять новый case при появлении domain'а)
 */
enum OrganizationType: string
{
    case SALON = 'salon';
    case RENTAL = 'rental';
    case CONSULT = 'consult';
    case OTHER = 'other';
}
