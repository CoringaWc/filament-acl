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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Author')
                ->options(User::query()->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
            TextInput::make('title')
                ->required()
                ->maxLength(255),
            TextInput::make('status')
                ->default('draft')
                ->required(),
            Select::make('categories')
                ->relationship('categories', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->columnSpanFull(),
            Textarea::make('content')
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')
                ->label('Author'),
            TextEntry::make('title'),
            TextEntry::make('status'),
            TextEntry::make('content'),
            TextEntry::make('categories_list')
                ->label('Categories')
                ->state(static fn (PostModel $record): string => $record->categories->pluck('name')->join(', ')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Author'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('categories_list')
                    ->label('Categories')
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
