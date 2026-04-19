<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource;

use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Modules\Identity\Interface\Filament\Action\ArchiveOrganizationAction;
use App\Modules\Identity\Interface\Filament\Action\VerifyOrganizationAction;
use App\Modules\Identity\Interface\Filament\Resource\OrganizationResource\Pages;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
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
 * Filament Resource для Organization — read-only list + view + admin actions.
 *
 * Создание/редактирование — через public API (см. OrganizationController).
 * Admin panel нужен только для observability, KYC (verify), force-archive.
 */
final class OrganizationResource extends Resource
{
    protected static ?string $model = OrganizationModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static string|UnitEnum|null $navigationGroup = 'Identity';

    protected static ?string $recordTitleAttribute = 'slug';

    protected static ?int $navigationSort = 20;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Overview')
                ->columns(2)
                ->schema([
                    TextEntry::make('id')->label('ID')->copyable(),
                    TextEntry::make('slug')->copyable(),
                    TextEntry::make('type')->badge(),
                    TextEntry::make('verified')
                        ->badge()
                        ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                        ->formatStateUsing(fn (bool $state): string => $state ? 'Verified' : 'Unverified'),
                    TextEntry::make('created_at')->dateTime(),
                    TextEntry::make('archived_at')->dateTime()->placeholder('—'),
                ]),
            Section::make('Profile')
                ->columns(2)
                ->schema([
                    TextEntry::make('name')
                        ->label('Name (RU)')
                        ->formatStateUsing(fn ($state): string => is_array($state) ? ($state['ru'] ?? '') : (string) $state),
                    TextEntry::make('city')->placeholder('—'),
                    TextEntry::make('district')->placeholder('—'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('email')->placeholder('—'),
                    TextEntry::make('cancellation_policy')->placeholder('—'),
                ]),
            Section::make('Reputation')
                ->columns(2)
                ->schema([
                    TextEntry::make('rating'),
                    TextEntry::make('reviews_count')->label('Reviews'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')->searchable()->copyable()->sortable(),
                TextColumn::make('name')
                    ->label('Name (RU)')
                    ->formatStateUsing(fn ($state): string => is_array($state) ? ($state['ru'] ?? '') : (string) $state)
                    ->searchable(query: function ($query, string $search) {
                        $query->whereRaw("(name->>'ru') ILIKE ? OR (name->>'en') ILIKE ?", ["%{$search}%", "%{$search}%"]);
                    }),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'salon' => 'primary',
                        'rental' => 'info',
                        'consult' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('city')->searchable()->toggleable(),
                IconColumn::make('verified')->boolean()->sortable(),
                TextColumn::make('rating')->sortable()->toggleable(),
                TextColumn::make('reviews_count')->label('Reviews')->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('archived_at')
                    ->dateTime()
                    ->label('Archived')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'salon' => 'Salon',
                    'rental' => 'Rental',
                    'consult' => 'Consult',
                    'other' => 'Other',
                ]),
                TernaryFilter::make('verified'),
                TernaryFilter::make('archived')
                    ->label('Archived')
                    ->placeholder('All')
                    ->trueLabel('Archived only')
                    ->falseLabel('Active only')
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('archived_at'),
                        false: fn ($q) => $q->whereNull('archived_at'),
                        blank: fn ($q) => $q,
                    ),
            ])
            ->recordActions([
                VerifyOrganizationAction::make(),
                ArchiveOrganizationAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'view' => Pages\ViewOrganization::route('/{record}'),
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

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
