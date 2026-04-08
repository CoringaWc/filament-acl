<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories;

use CoringaWc\FilamentAcl\Attributes\CustomPermissionActions;
use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use Filament\Actions\Action;
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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Enums\NavigationGroup;
use Workbench\App\Filament\Resources\Categories\Pages\CreateCategory;
use Workbench\App\Filament\Resources\Categories\Pages\EditCategory;
use Workbench\App\Filament\Resources\Categories\Pages\ListCategories;
use Workbench\App\Filament\Resources\Categories\Pages\ViewCategory;
use Workbench\App\Filament\Resources\Categories\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Category;

#[CustomPermissionActions(['archive'])]
class CategoryResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Category::class;

    protected static \BackedEnum | string | null $navigationIcon = Heroicon::OutlinedTag;

    public static function getModelLabel(): string
    {
        return __('workbench::workbench.resources.categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('workbench::workbench.resources.categories.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('workbench::workbench.resources.categories.navigation_label');
    }

    public static function getNavigationGroup(): \BackedEnum | string | null
    {
        return NavigationGroup::Blog;
    }

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
                Action::make('archive')
                    ->label(__('workbench::workbench.actions.archive.label'))
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('workbench::workbench.actions.archive.modal_heading'))
                    ->modalDescription(__('workbench::workbench.actions.archive.modal_description'))
                    ->authorize('archive', CategoryResource::class)
                    ->visible(fn (Category $record): bool => $record->description !== null)
                    ->action(function (Category $record): void {
                        $record->update(['description' => null]);
                    }),
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
