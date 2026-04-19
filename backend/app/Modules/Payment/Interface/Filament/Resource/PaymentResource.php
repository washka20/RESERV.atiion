<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Resource;

use App\Modules\Booking\Interface\Filament\Resource\BookingResource;
use App\Modules\Payment\Infrastructure\Persistence\Model\PaymentModel;
use App\Modules\Payment\Interface\Filament\Action\MarkPaymentPaidAction;
use App\Modules\Payment\Interface\Filament\Action\RefundPaymentAction;
use App\Modules\Payment\Interface\Filament\Resource\PaymentResource\Pages;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Filament Resource для Payment aggregate — read-only список + admin actions (markPaid, refund).
 *
 * Платежи создаются только событиями (InitiatePaymentOnBookingCreated), а переходят статусы
 * через gateway callbacks или ручные actions. Create/edit/delete в админке запрещены.
 */
final class PaymentResource extends Resource
{
    protected static ?string $model = PaymentModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Payment';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('booking_id')
                    ->label('Booking')
                    ->copyable()
                    ->url(fn (PaymentModel $record): string => BookingResource::getUrl('view', ['record' => $record->booking_id]))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8).'…'),
                TextColumn::make('amount_cents')
                    ->label('Amount')
                    ->money('rub', divideBy: 100),
                TextColumn::make('platform_fee_cents')
                    ->label('Fee')
                    ->money('rub', divideBy: 100)
                    ->toggleable(),
                TextColumn::make('net_amount_cents')
                    ->label('Net')
                    ->money('rub', divideBy: 100),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'success',
                        'refunded' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('method')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('provider_ref')
                    ->label('Provider ref')
                    ->limit(20)
                    ->tooltip(fn (PaymentModel $record): ?string => $record->provider_ref)
                    ->toggleable(),
                TextColumn::make('paid_at')->dateTime()->label('Paid at')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'paid' => 'Paid',
                    'refunded' => 'Refunded',
                    'failed' => 'Failed',
                ]),
                SelectFilter::make('method')->options([
                    'card' => 'Card',
                    'bank_transfer' => 'Bank transfer',
                    'sbp' => 'SBP',
                    'cash' => 'Cash',
                ]),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')->label('From'),
                        DatePicker::make('created_until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                MarkPaymentPaidAction::make(),
                RefundPaymentAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * @param  mixed  $record
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * @param  mixed  $record
     */
    public static function canDelete($record): bool
    {
        return false;
    }
}
