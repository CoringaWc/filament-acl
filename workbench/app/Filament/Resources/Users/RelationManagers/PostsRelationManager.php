<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Users\RelationManagers;

use CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;

class PostsRelationManager extends RelationManager
{
    use EnsuresValidationErrorBag;
    use HasRelationManagerPermissions;

    protected static string $relationship = 'posts';

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')
                ->label(__('workbench::workbench.resources.posts.fields.title'))
                ->required(),
            TextInput::make('status')
                ->label(__('workbench::workbench.resources.posts.fields.status'))
                ->default('draft')
                ->required(),
            Textarea::make('content')
                ->label(__('workbench::workbench.resources.posts.fields.content'))
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('title')
                    ->label(__('workbench::workbench.resources.posts.columns.title'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('workbench::workbench.resources.posts.columns.status'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => __('workbench::workbench.post_statuses.' . $state)),
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
}
