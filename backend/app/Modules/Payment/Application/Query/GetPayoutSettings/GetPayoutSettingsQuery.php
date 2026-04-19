<?php

declare(strict_types=1);

namespace App\Modules\Payment\Application\Query\GetPayoutSettings;

/**
 * Запрос: настройки выплат организации. Возвращает null если ещё не сконфигурированы.
 */
final readonly class GetPayoutSettingsQuery
{
    public function __construct(public string $organizationId) {}
}
