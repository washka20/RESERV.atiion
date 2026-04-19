<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Resource\PayoutTransactionResource\Pages;

use App\Modules\Payment\Interface\Filament\Action\ProcessPayoutBatchAction;
use App\Modules\Payment\Interface\Filament\Resource\PayoutTransactionResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Страница списка выплат. Header action «processBatch» запускает batch обработку pending payouts.
 */
final class ListPayoutTransactions extends ListRecords
{
    protected static string $resource = PayoutTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProcessPayoutBatchAction::make(),
        ];
    }
}
