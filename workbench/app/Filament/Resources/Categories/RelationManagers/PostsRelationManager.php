<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\RelationManagers;

use CoringaWc\FilamentAcl\RelationManagers\Concerns\HasRelationManagerPermissions;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DetachAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource as NestedPostResource;
use Workbench\App\Models\User;

class PostsRelationManager extends RelationManager
{
    use EnsuresValidationErrorBag;
    use HasRelationManagerPermissions;

    protected static string $relationship = 'posts';

    protected static ?string $relatedResource = NestedPostResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }

    public static function getSharedPermissionOwner(): ?string
    {
        return NestedPostResource::class;
    }

    public function form(Schema $schema): Schema
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
            Textarea::make('content')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
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
