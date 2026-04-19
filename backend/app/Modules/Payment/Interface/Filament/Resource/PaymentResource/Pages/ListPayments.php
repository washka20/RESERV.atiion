<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Resource\PaymentResource\Pages;

use App\Modules\Payment\Interface\Filament\Resource\PaymentResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка платежей. Без header actions — создание только через события домена.
 */
final class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
