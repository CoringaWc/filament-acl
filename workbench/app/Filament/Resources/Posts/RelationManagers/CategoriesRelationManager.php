<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Posts\RelationManagers;

use CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedCategoryResource;

class CategoriesRelationManager extends RelationManager
{
    use EnsuresValidationErrorBag;
    use HasRelationManagerPermissions;

    protected static string $relationship = 'categories';

    protected static ?string $relatedResource = NestedCategoryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }

    public static function getSharedPermissionOwner(): ?string
    {
        return NestedCategoryResource::class;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('workbench::workbench.resources.categories.fields.name'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('workbench::workbench.resources.categories.fields.description'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label(__('workbench::workbench.resources.categories.columns.name'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('workbench::workbench.resources.categories.columns.description'))
                    ->limit(40),
            ])
            ->headerActions([
                CreateAction::make(),
                AttachAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DetachAction::make(),
                DeleteAction::make(),
            ]);
    }
}
