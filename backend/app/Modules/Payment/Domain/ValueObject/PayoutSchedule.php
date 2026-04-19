<?php

declare(strict_types=1);

namespace App\Modules\Payment\Domain\ValueObject;

/**
 * Расписание выплат организации.
 *
 * WEEKLY — еженедельно (вторник), BIWEEKLY — раз в 2 недели,
 * MONTHLY — ежемесячно, ON_REQUEST — только по ручному запросу (worker пропускает).
 */
enum PayoutSchedule: string
{
    case WEEKLY = 'weekly';
    case BIWEEKLY = 'biweekly';
    case MONTHLY = 'monthly';
    case ON_REQUEST = 'on_request';
}
