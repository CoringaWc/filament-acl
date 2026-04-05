<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\ModerationPosts;

use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\ModerationPosts\Pages\ListPosts;
use Workbench\App\Filament\Resources\ModerationPosts\Pages\ViewPost;
use Workbench\App\Models\Post;

class PostResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Post::class;

    protected static ?Heroicon $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getModelLabel(): string
    {
        return __('workbench::workbench.resources.moderation_posts.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('workbench::workbench.resources.moderation_posts.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('workbench::workbench.resources.moderation_posts.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('workbench::workbench.resources.moderation_posts.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label(__('workbench::workbench.resources.moderation_posts.fields.title'))
                ->disabled(),
            TextInput::make('status')
                ->label(__('workbench::workbench.resources.moderation_posts.fields.status'))
                ->disabled(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')
                ->label(__('workbench::workbench.resources.moderation_posts.fields.title')),
            TextEntry::make('status')
                ->label(__('workbench::workbench.resources.moderation_posts.fields.status')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label(__('workbench::workbench.resources.moderation_posts.columns.title'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('workbench::workbench.resources.moderation_posts.columns.status'))
                    ->badge(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    /**
     * @return array<string, class-string>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'view' => ViewPost::route('/{record}'),
        ];
    }
}
