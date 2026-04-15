<?php

declare(strict_types=1);

namespace App\Modules\Identity\Interface\Filament\Resource;

use App\Modules\Identity\Infrastructure\Persistence\Model\UserModel;
use App\Modules\Identity\Interface\Filament\Action\AssignRoleAction;
use App\Modules\Identity\Interface\Filament\Action\RevokeRoleAction;
use App\Modules\Identity\Interface\Filament\Resource\UserResource\Pages;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role as SpatieRole;
use UnitEnum;

final class UserResource extends Resource
{
    protected static ?string $model = UserModel::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Identity';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (string $operation): bool => $operation === 'create')
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->minLength(8),
            TextInput::make('first_name')->required()->maxLength(100),
            TextInput::make('last_name')->required()->maxLength(100),
            TextInput::make('middle_name')->maxLength(100),
            Select::make('roles')
                ->multiple()
                ->relationship('roles', 'name')
                ->options(fn () => SpatieRole::pluck('name', 'name'))
                ->dehydrated(false)
                ->disabled(),
            DateTimePicker::make('email_verified_at')->label('Verified at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->searchable()->sortable(),
                TextColumn::make('full_name')->label('Full name')->searchable(['last_name', 'first_name']),
                TextColumn::make('roles.name')->badge()->separator(','),
                TextColumn::make('email_verified_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('roles')->relationship('roles', 'name'),
                TernaryFilter::make('email_verified_at')
                    ->label('Verified')
                    ->nullable(),
            ])
            ->recordActions([
                EditAction::make(),
                AssignRoleAction::make(),
                RevokeRoleAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'manager']) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
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
