<?php

declare(strict_types=1);

namespace App\Modules\Payment\Interface\Filament\Resource;

use App\Modules\Booking\Interface\Filament\Resource\BookingResource;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Modules\Identity\Interface\Filament\Resource\OrganizationResource;
use App\Modules\Payment\Infrastructure\Persistence\Model\PayoutTransactionModel;
use App\Modules\Payment\Interface\Filament\Action\MarkPayoutPaidAction;
use App\Modules\Payment\Interface\Filament\Resource\PayoutTransactionResource\Pages;
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
 * Filament Resource для PayoutTransaction — read-only list + admin actions (markPaid, processBatch).
 *
 * Создаются событием PaymentReceived, выплачиваются batch-командой app:payouts:process
 * по расписанию организации. В админке можно форсить batch или помечать отдельные payout как paid.
 */
final class PayoutTransactionResource extends Resource
{
    protected static ?string $model = PayoutTransactionModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Payment';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?string $pluralModelLabel = 'Payout transactions';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('organization_id')
                    ->label('Organization')
                    ->url(fn (PayoutTransactionModel $record): string => OrganizationResource::getUrl('view', ['record' => $record->organization_id]))
                    ->openUrlInNewTab()
                    ->formatStateUsing(function (string $state): string {
                        $org = OrganizationModel::query()->find($state);
                        if ($org === null) {
                            return substr($state, 0, 8).'…';
                        }

                        return (string) ($org->slug ?? substr($state, 0, 8).'…');
                    })
                    ->searchable(),
                TextColumn::make('booking_id')
                    ->label('Booking')
                    ->copyable()
                    ->url(fn (PayoutTransactionModel $record): string => BookingResource::getUrl('view', ['record' => $record->booking_id]))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8).'…')
                    ->toggleable(),
                TextColumn::make('gross_amount_cents')
                    ->label('Gross')
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
                        'processing' => 'info',
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('scheduled_at')->dateTime()->label('Scheduled')->sortable()->toggleable(),
                TextColumn::make('paid_at')->dateTime()->label('Paid')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'paid' => 'Paid',
                    'failed' => 'Failed',
                ]),
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->relationship('organization', 'slug')
                    ->searchable()
                    ->preload(),
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
                MarkPayoutPaidAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayoutTransactions::route('/'),
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
