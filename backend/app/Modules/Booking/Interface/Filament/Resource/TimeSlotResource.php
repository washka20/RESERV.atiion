<?php

declare(strict_types=1);

namespace App\Modules\Booking\Interface\Filament\Resource;

use App\Modules\Booking\Infrastructure\Persistence\Model\TimeSlotModel;
use App\Modules\Booking\Interface\Filament\Resource\TimeSlotResource\Pages;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/**
 * Filament Resource для TimeSlot — read-only просмотр слотов.
 *
 * Создание слотов идёт только через кастомную GenerateTimeSlotsPage (batch-генерация),
 * которая диспатчит GenerateTimeSlotsCommand через CommandBus. Edit/Delete запрещены:
 * слоты либо свободны, либо забронированы (освобождение идёт через ReleaseTimeSlotCommand
 * при отмене бронирования).
 */
final class TimeSlotResource extends Resource
{
    protected static ?string $model = TimeSlotModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|UnitEnum|null $navigationGroup = 'Booking';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Infolist detail page — все поля в read-only виде.
     */
    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Slot')
                ->columns(2)
                ->schema([
                    TextEntry::make('id')->label('Slot ID')->copyable(),
                    TextEntry::make('service.name')->label('Service'),
                    TextEntry::make('service_id')->label('Service ID')->copyable(),
                    TextEntry::make('is_booked')
                        ->badge()
                        ->color(fn ($state): string => $state ? 'danger' : 'success')
                        ->formatStateUsing(fn ($state): string => $state ? 'Booked' : 'Free'),
                ]),
            Section::make('Schedule')
                ->columns(2)
                ->schema([
                    TextEntry::make('start_at')->dateTime()->label('Start at'),
                    TextEntry::make('end_at')->dateTime()->label('End at'),
                ]),
            Section::make('Booking')
                ->columns(2)
                ->schema([
                    TextEntry::make('booking_id')->label('Booking ID')->copyable()->placeholder('—'),
                    TextEntry::make('created_at')->dateTime()->label('Created'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service.name')->label('Service')->searchable(),
                TextColumn::make('start_at')->dateTime()->label('Start')->sortable(),
                TextColumn::make('end_at')->dateTime()->label('End')->toggleable(),
                TextColumn::make('is_booked')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state): string => $state ? 'Booked' : 'Free'),
                TextColumn::make('booking_id')
                    ->label('Booking')
                    ->toggleable()
                    ->placeholder('—')
                    ->copyable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name')
                    ->searchable(),
                TernaryFilter::make('is_booked')->label('Booked'),
                Filter::make('date_range')
                    ->schema([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('start_at', '>=', $date),
                            )
                            ->when(
                                $data['to'] ?? null,
                                fn (Builder $q, string $date): Builder => $q->whereDate('start_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('start_at', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTimeSlots::route('/'),
            'view' => Pages\ViewTimeSlot::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    /**
     * Создание слотов — только через GenerateTimeSlotsPage (batch).
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Слоты неизменяемы — is_booked меняется только через ReleaseTimeSlotCommand
     * из домена Booking (при отмене бронирования).
     *
     * @param  Model  $record
     */
    public static function canEdit($record): bool
    {
        return false;
    }

    /**
     * Hard delete запрещён — слоты могут быть связаны с бронированиями.
     *
     * @param  Model  $record
     */
    public static function canDelete($record): bool
    {
        return false;
    }
}
