<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories;

use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Categories\Pages\CreateCategory;
use Workbench\App\Filament\Resources\Categories\Pages\EditCategory;
use Workbench\App\Filament\Resources\Categories\Pages\ListCategories;
use Workbench\App\Filament\Resources\Categories\Pages\ViewCategory;
use Workbench\App\Filament\Resources\Categories\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Category;

class CategoryResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Category::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('workbench::workbench.resources.categories.fields.name'))
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->label(__('workbench::workbench.resources.categories.fields.description'))
                ->columnSpanFull(),
            Select::make('posts')
                ->label(__('workbench::workbench.resources.categories.fields.posts'))
                ->relationship('posts', 'title')
                ->multiple()
                ->preload()
                ->searchable()
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')
                ->label(__('workbench::workbench.resources.categories.fields.name')),
            TextEntry::make('description')
                ->label(__('workbench::workbench.resources.categories.fields.description')),
            TextEntry::make('posts_list')
                ->label(__('workbench::workbench.resources.categories.fields.posts'))
                ->state(static fn (Category $record): string => $record->posts->pluck('title')->join(', ')),
        ]);
    }

    public static function table(Table $table): Table
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
                TextColumn::make('posts_count')
                    ->label(__('workbench::workbench.resources.categories.columns.posts_count'))
                    ->counts('posts')
                    ->badge(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    /**
     * @return array<string, class-string>
     */
    public static function getRelations(): array
    {
        return [
            'posts' => PostsRelationManager::class,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'view' => ViewCategory::route('/{record}'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
