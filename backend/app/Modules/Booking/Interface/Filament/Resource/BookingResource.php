<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Resource;

use App\Modules\Booking\Infrastructure\Persistence\Model\BookingModel;
use App\Modules\Booking\Interface\Filament\Action\CancelBookingAction;
use App\Modules\Booking\Interface\Filament\Action\CompleteBookingAction;
use App\Modules\Booking\Interface\Filament\Action\ConfirmBookingAction;
use App\Modules\Booking\Interface\Filament\Resource\BookingResource\Pages;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Filament Resource для Booking aggregate — read-only список + read-only view + actions.
 *
 * Создание бронирований идёт только через customer API (Booking::createTimeSlotBooking / createQuantityBooking
 * и CreateBookingHandler). Edit/Delete запрещены; переходы статусов — через custom actions,
 * которые диспатчат Command через CommandBus.
 */
final class BookingResource extends Resource
{
    protected static ?string $model = BookingModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Booking';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Infolist detail page — все поля в read-only виде. Форма не нужна: create/edit запрещены.
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overview')
                ->columns(2)
                ->schema([
                    TextEntry::make('id')->label('Booking ID')->copyable(),
                    TextEntry::make('status')->badge()->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'primary',
                        default => 'gray',
                    }),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('created_at')->dateTime()->label('Created'),
                ]),
            Section::make('User & Service')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.email')->label('User'),
                    TextEntry::make('user_id')->label('User ID')->copyable(),
                    TextEntry::make('service.name')->label('Service'),
                    TextEntry::make('service_id')->label('Service ID')->copyable(),
                ]),
            Section::make('Schedule')
                ->columns(2)
                ->schema([
                    TextEntry::make('slot_id')->label('Slot ID')->copyable()->placeholder('—'),
                    TextEntry::make('start_at')->dateTime()->label('Start at')->placeholder('—'),
                    TextEntry::make('end_at')->dateTime()->label('End at')->placeholder('—'),
                    TextEntry::make('check_in')->date()->label('Check-in')->placeholder('—'),
                    TextEntry::make('check_out')->date()->label('Check-out')->placeholder('—'),
                    TextEntry::make('quantity')->placeholder('—'),
                ]),
            Section::make('Pricing')
                ->columns(2)
                ->schema([
                    TextEntry::make('total_price_amount')->label('Amount'),
                    TextEntry::make('total_price_currency')->label('Currency'),
                ]),
            Section::make('Notes')
                ->schema([
                    TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')->label('User')->searchable()->toggleable(),
                TextColumn::make('service.name')->label('Service')->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'time_slot' => 'primary',
                        'quantity' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('start_at')->dateTime()->label('Start')->sortable(),
                TextColumn::make('check_in')->date()->label('Check-in')->toggleable(),
                TextColumn::make('check_out')->date()->label('Check-out')->toggleable(),
                TextColumn::make('quantity')->toggleable(),
                TextColumn::make('total_price_amount')->label('Total')->money('rub'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                    'completed' => 'Completed',
                ]),
                SelectFilter::make('type')->options([
                    'time_slot' => 'Time slot',
                    'quantity' => 'Quantity',
                ]),
                SelectFilter::make('service_id')->relationship('service', 'name')->label('Service'),
            ])
            ->recordActions([
                ConfirmBookingAction::make(),
                CompleteBookingAction::make(),
                CancelBookingAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    /**
     * Создание бронирований — только через customer API (CreateBookingHandler).
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Поля бронирования неизменяемы — state-переходы через actions (confirm/complete/cancel).
     *
     * @param  Model  $record
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Hard delete запрещён — бронирования исторически важны для аудита.
     *
     * @param  Model  $record
     */
    public static function canDelete($record): bool
    {
        return false;
    }
}
