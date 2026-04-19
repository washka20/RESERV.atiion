<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource;

use App\Modules\Catalog\Infrastructure\Persistence\Model\CategoryModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\ServiceModel;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use App\Modules\Catalog\Interface\Filament\Action\ActivateServiceAction;
use App\Modules\Catalog\Interface\Filament\Action\DeactivateServiceAction;
use App\Modules\Catalog\Interface\Filament\Resource\ServiceResource\Pages;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament Resource для услуг каталога.
 *
 * Write — через CommandBus в Pages. Reads — Eloquent ServiceModel.
 * Поддерживает TIME_SLOT и QUANTITY услуги с conditional полями формы.
 */
final class ServiceResource extends Resource
{
    protected static ?string $model = ServiceModel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = 'Каталог';

    protected static ?string $label = 'Услуга';

    protected static ?string $pluralLabel = 'Услуги';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Основное')
                ->schema([
                    TextInput::make('name')
                        ->label('Название')
                        ->required()
                        ->maxLength(200),
                    Textarea::make('description')
                        ->label('Описание')
                        ->required()
                        ->minLength(10)
                        ->rows(4),
                    Select::make('organization_id')
                        ->label('Организация')
                        ->options(fn () => OrganizationModel::query()
                            ->whereNull('archived_at')
                            ->orderBy('slug')
                            ->pluck('slug', 'id'))
                        ->required()
                        ->searchable()
                        ->disabled(fn (string $operation): bool => $operation === 'edit')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                    Select::make('category_id')
                        ->label('Категория')
                        ->options(fn () => CategoryModel::query()->orderBy('sort_order')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('subcategory_id', null)),
                    Select::make('subcategory_id')
                        ->label('Подкатегория')
                        ->options(function (callable $get) {
                            $categoryId = $get('category_id');
                            if (! $categoryId) {
                                return [];
                            }

                            return SubcategoryModel::query()
                                ->where('category_id', $categoryId)
                                ->orderBy('sort_order')
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->nullable(),
                ])
                ->columns(2),

            Section::make('Тип и параметры')
                ->schema([
                    Select::make('type')
                        ->label('Тип услуги')
                        ->options([
                            'time_slot' => 'Слот по времени',
                            'quantity' => 'Количество',
                        ])
                        ->required()
                        ->reactive()
                        ->disabled(fn (string $operation): bool => $operation === 'edit')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                    TextInput::make('duration_minutes')
                        ->label('Длительность (мин)')
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn (callable $get): bool => $get('type') === 'time_slot')
                        ->required(fn (callable $get): bool => $get('type') === 'time_slot'),
                    TextInput::make('total_quantity')
                        ->label('Общее количество')
                        ->numeric()
                        ->minValue(1)
                        ->visible(fn (callable $get): bool => $get('type') === 'quantity')
                        ->required(fn (callable $get): bool => $get('type') === 'quantity'),
                ])
                ->columns(2),

            Section::make('Цена')
                ->schema([
                    TextInput::make('price_amount')
                        ->label('Цена (в копейках)')
                        ->helperText('10000 = 100.00 RUB')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->required(),
                    Select::make('price_currency')
                        ->label('Валюта')
                        ->options([
                            'RUB' => 'RUB',
                            'USD' => 'USD',
                            'EUR' => 'EUR',
                        ])
                        ->default('RUB')
                        ->required(),
                ])
                ->columns(2),

            Section::make('Статус')
                ->schema([
                    Toggle::make('is_active')
                        ->label('Активна')
                        ->default(true)
                        ->dehydrated(false)
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'time_slot' ? 'primary' : 'success')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'time_slot' => 'Слот',
                        'quantity' => 'Кол-во',
                        default => $state,
                    }),
                TextColumn::make('category.name')->label('Категория')->sortable(),
                TextColumn::make('subcategory.name')->label('Подкатегория')->toggleable(),
                TextColumn::make('price_amount')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state, $record): string => number_format(((int) $state) / 100, 2, '.', ' ')
                        .' '.($record->price_currency ?? ''))
                    ->sortable(),
                IconColumn::make('is_active')->label('Активна')->boolean()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Категория')
                    ->options(fn () => CategoryModel::query()->orderBy('sort_order')->pluck('name', 'id')),
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'time_slot' => 'Слот',
                        'quantity' => 'Количество',
                    ]),
                TernaryFilter::make('is_active')->label('Активна'),
            ])
            ->recordActions([
                EditAction::make(),
                DeactivateServiceAction::make()->visible(fn (ServiceModel $record): bool => (bool) $record->is_active),
                ActivateServiceAction::make()->visible(fn (ServiceModel $record): bool => ! $record->is_active),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }
}
