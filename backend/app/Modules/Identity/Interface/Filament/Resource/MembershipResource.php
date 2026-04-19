<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource;

use App\Modules\Identity\Infrastructure\Persistence\Model\MembershipModel;
use App\Modules\Identity\Infrastructure\Persistence\Model\OrganizationModel;
use App\Modules\Identity\Interface\Filament\Resource\MembershipResource\Pages;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Filament Resource для Membership — fully read-only.
 *
 * Invite/revoke/change-role — через public API (OrganizationMembersController).
 * Admin panel даёт observability (кто в каких org'ах, как давно, в какой роли).
 */
final class MembershipResource extends Resource
{
    protected static ?string $model = MembershipModel::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Identity';

    protected static ?int $navigationSort = 30;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.email')->label('User')->searchable()->sortable(),
                TextColumn::make('organization.slug')->label('Org slug')->searchable()->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'owner' => 'success',
                        'admin' => 'primary',
                        'staff' => 'info',
                        'viewer' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('accepted_at')->dateTime()->placeholder('—')->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization_id')
                    ->label('Organization')
                    ->options(fn () => OrganizationModel::query()
                        ->whereNull('archived_at')
                        ->orderBy('slug')
                        ->pluck('slug', 'id')),
                SelectFilter::make('role')->options([
                    'owner' => 'Owner',
                    'admin' => 'Admin',
                    'staff' => 'Staff',
                    'viewer' => 'Viewer',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMemberships::route('/'),
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
