<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Interface\Filament\Resource\CategoryResource\RelationManagers;

use App\Modules\Catalog\Application\Command\CreateSubcategory\CreateSubcategoryCommand;
use App\Modules\Catalog\Application\Command\DeleteSubcategory\DeleteSubcategoryCommand;
use App\Modules\Catalog\Application\Command\UpdateSubcategory\UpdateSubcategoryCommand;
use App\Modules\Catalog\Infrastructure\Persistence\Model\SubcategoryModel;
use App\Shared\Application\Bus\CommandBusInterface;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Relation manager подкатегорий внутри Category edit page.
 *
 * Create/Update/Delete — через CommandBus. Read — Eloquent relation.
 */
final class SubcategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'subcategories';

    protected static ?string $title = 'Подкатегории';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Название')->required()->maxLength(120),
            TextInput::make('slug')
                ->label('Slug')
                ->required()
                ->maxLength(120)
                ->alphaDash()
                ->disabled(fn (string $operation): bool => $operation === 'edit')
                ->dehydrated(fn (string $operation): bool => $operation === 'create'),
            TextInput::make('sort_order')->label('Порядок')->numeric()->default(0)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->label('Название')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable(),
                TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        $categoryId = (string) $this->getOwnerRecord()->id;

                        app(CommandBusInterface::class)->dispatch(new CreateSubcategoryCommand(
                            categoryId: $categoryId,
                            name: $data['name'],
                            slug: $data['slug'],
                            sortOrder: (int) ($data['sort_order'] ?? 0),
                        ));

                        return SubcategoryModel::query()
                            ->where('category_id', $categoryId)
                            ->where('slug', $data['slug'])
                            ->firstOrFail();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (Model $record, array $data): Model {
                        app(CommandBusInterface::class)->dispatch(new UpdateSubcategoryCommand(
                            categoryId: (string) $record->category_id,
                            subcategoryId: (string) $record->id,
                            name: $data['name'],
                            sortOrder: (int) ($data['sort_order'] ?? 0),
                        ));

                        return $record->refresh();
                    }),
                DeleteAction::make()
                    ->using(function (Model $record): void {
                        app(CommandBusInterface::class)->dispatch(new DeleteSubcategoryCommand(
                            categoryId: (string) $record->category_id,
                            subcategoryId: (string) $record->id,
                        ));
                    }),
            ]);
    }
}
