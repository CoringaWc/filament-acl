<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Posts;

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
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Enums\NavigationGroup;
use Workbench\App\Filament\Resources\Posts\Pages\CreatePost;
use Workbench\App\Filament\Resources\Posts\Pages\EditPost;
use Workbench\App\Filament\Resources\Posts\Pages\ListPosts;
use Workbench\App\Filament\Resources\Posts\Pages\ViewPost;
use Workbench\App\Filament\Resources\Posts\RelationManagers\CategoriesRelationManager;
use Workbench\App\Models\Post;
use Workbench\App\Models\Post as PostModel;
use Workbench\App\Models\User;

class PostResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Post::class;

    protected static \BackedEnum | string | null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getModelLabel(): string
    {
        return __('workbench::workbench.resources.posts.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('workbench::workbench.resources.posts.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('workbench::workbench.resources.posts.navigation_label');
    }

    public static function getNavigationGroup(): \BackedEnum | string | null
    {
        return NavigationGroup::Blog;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label(__('workbench::workbench.resources.posts.fields.author'))
                ->options(User::query()->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
            TextInput::make('title')
                ->label(__('workbench::workbench.resources.posts.fields.title'))
                ->required()
                ->maxLength(255),
            TextInput::make('status')
                ->label(__('workbench::workbench.resources.posts.fields.status'))
                ->default('draft')
                ->required(),
            Select::make('categories')
                ->label(__('workbench::workbench.resources.posts.fields.categories'))
                ->relationship('categories', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->columnSpanFull(),
            Textarea::make('content')
                ->label(__('workbench::workbench.resources.posts.fields.content'))
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')
                ->label(__('workbench::workbench.resources.posts.fields.author')),
            TextEntry::make('title')
                ->label(__('workbench::workbench.resources.posts.fields.title')),
            TextEntry::make('status')
                ->label(__('workbench::workbench.resources.posts.fields.status')),
            TextEntry::make('content')
                ->label(__('workbench::workbench.resources.posts.fields.content')),
            TextEntry::make('categories_list')
                ->label(__('workbench::workbench.resources.posts.fields.categories'))
                ->state(static fn (PostModel $record): string => $record->categories->pluck('name')->join(', ')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label(__('workbench::workbench.resources.posts.columns.title'))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('workbench::workbench.resources.posts.columns.author')),
                TextColumn::make('status')
                    ->label(__('workbench::workbench.resources.posts.columns.status'))
                    ->badge(),
                TextColumn::make('categories_list')
                    ->label(__('workbench::workbench.resources.posts.columns.categories'))
                    ->state(static fn (PostModel $record): string => $record->categories->pluck('name')->join(', '))
                    ->wrap(),
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
            'categories' => CategoriesRelationManager::class,
        ];
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
