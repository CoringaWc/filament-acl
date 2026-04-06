<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Users;

use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\Pages\ListUsers;
use Workbench\App\Filament\Resources\Users\Pages\ViewUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

class UserResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = User::class;

    protected static \BackedEnum | string | null $navigationIcon = Heroicon::OutlinedUsers;

    public static function getModelLabel(): string
    {
        return __('workbench::workbench.resources.users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('workbench::workbench.resources.users.plural_model_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('workbench::workbench.resources.users.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('workbench::workbench.resources.users.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('workbench::workbench.resources.users.fields.name'))
                ->required(),
            TextInput::make('email')
                ->label(__('workbench::workbench.resources.users.fields.email'))
                ->email()
                ->required(),
            Select::make('roles')
                ->label(__('workbench::workbench.resources.users.fields.roles'))
                ->disabled(static fn (?User $record): bool => $record?->hasRole(Utils::getProtectedRoleName()) ?? false)
                ->relationship(
                    name: 'roles',
                    titleAttribute: 'name',
                    modifyQueryUsing: static fn (Builder $query): Builder => Utils::scopeRoleQueryToPanel(
                        Utils::scopeVisibleRoles($query->orderBy('name')),
                        Filament::getCurrentPanel()?->getId(),
                    ),
                )
                ->getOptionLabelFromRecordUsing(static fn (Role $record): string => Str::headline($record->name))
                ->multiple()
                ->preload()
                ->searchable()
                ->loadStateFromRelationshipsUsing(static function (Select $component, User $record): void {
                    $component->state(
                        $record->roles()
                            ->whereNotIn('id', Utils::getHiddenRoleIds(Filament::getCurrentPanel()?->getId()))
                            ->pluck('id')
                            ->all(),
                    );
                })
                ->saveRelationshipsUsing(static function (Select $component, User $record, mixed $state): void {
                    /** @var array<int, int|string> $roleIds */
                    $roleIds = array_values(array_filter(is_array($state) ? $state : []));
                    $mergedRoleIds = Utils::mergeHiddenRoleIds($record, $roleIds, Filament::getCurrentPanel()?->getId());

                    $record->syncRoles(
                        Role::query()
                            ->whereKey($mergedRoleIds)
                            ->get(),
                    );
                }),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')
                ->label(__('workbench::workbench.resources.users.fields.name')),
            TextEntry::make('email')
                ->label(__('workbench::workbench.resources.users.fields.email')),
            TextEntry::make('roles.name')
                ->label(__('workbench::workbench.resources.users.fields.roles'))
                ->badge()
                ->state(static fn (User $record): string => $record->roles
                    ->reject(static fn (Model $role): bool => Utils::isProtectedRole($role))
                    ->pluck('name')
                    ->map(static fn (string $name): string => Str::headline($name))
                    ->join(', ')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label(__('workbench::workbench.resources.users.columns.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('workbench::workbench.resources.users.columns.email'))
                    ->searchable(),
                TextColumn::make('visible_roles')
                    ->label(__('workbench::workbench.resources.users.columns.roles'))
                    ->badge()
                    ->state(static fn (User $record): string => $record->roles
                        ->reject(static fn (Model $role): bool => Utils::isProtectedRole($role))
                        ->pluck('name')
                        ->map(static fn (string $name): string => Str::headline($name))
                        ->join(', ')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * @return array<int, class-string>
     */
    public static function getRelations(): array
    {
        return [
            PostsRelationManager::class,
        ];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
