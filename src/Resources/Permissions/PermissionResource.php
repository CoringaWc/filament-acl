<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Permissions;

use BackedEnum;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

class PermissionResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $configurationClass = PermissionResourceConfiguration::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return app(StoresPermissions::class)->getRoleModel();
    }

    public static function getPermissionSubject(): ?string
    {
        return 'FilamentPermissions';
    }

    /**
     * @return Builder<Model>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = Utils::scopeVisibleRoles(parent::getEloquentQuery());

        if (! static::shouldScopeRolesToCurrentPanel()) {
            return $query;
        }

        return $query->where(
            static::getPanelColumnName(),
            static::resolveCurrentPanelScopeValue(),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make()
                ->schema([
                    Section::make()
                        ->schema([
                            TextInput::make('name')
                                ->label(__('filament-acl::filament-acl.resources.permissions.fields.name'))
                                ->required()
                                ->maxLength(255)
                                ->rule(
                                    Rule::notIn([
                                        Utils::getProtectedRoleName(),
                                    ]),
                                )
                                ->unique(
                                    ignoreRecord: true,
                                    modifyRuleUsing: function (Unique $rule): Unique {
                                        $rule->where('guard_name', static::getDefaultGuardName());

                                        if (static::shouldScopeRolesToCurrentPanel()) {
                                            $rule->where(
                                                static::getPanelColumnName(),
                                                static::resolveCurrentPanelScopeValue(),
                                            );
                                        }

                                        return $rule;
                                    },
                                ),
                            Hidden::make('guard_name')
                                ->default(static::getDefaultGuardName()),
                            Hidden::make(static::getPanelColumnName())
                                ->default(static::resolveCurrentPanelScopeValue())
                                ->dehydrated(static::shouldScopeRolesToCurrentPanel()),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                    Tabs::make('permission_groups_tabs')
                        ->tabs(static::getPermissionManagementTabs())
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->columns([
                TextColumn::make('name')
                    ->label(__('filament-acl::filament-acl.resources.permissions.columns.name'))
                    ->weight('font-medium')
                    ->formatStateUsing(static fn (string $state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->label(__('filament-acl::filament-acl.resources.permissions.columns.guard_name'))
                    ->badge(),
                TextColumn::make(static::getPanelColumnName())
                    ->label(__('filament-acl::filament-acl.resources.permissions.columns.panel'))
                    ->badge()
                    ->visible(static fn (): bool => static::shouldScopeRolesToCurrentPanel()),
                TextColumn::make('permissions_count')
                    ->label(__('filament-acl::filament-acl.resources.permissions.columns.permissions_count'))
                    ->counts('permissions')
                    ->badge()
                    ->color('success'),
                TextColumn::make('updated_at')
                    ->label(__('filament-acl::filament-acl.resources.permissions.columns.updated_at'))
                    ->since()
                    ->dateTimeTooltip('Y-m-d H:i:s'),
            ])
            ->recordUrl(null)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return static::getPermissionResourceConfiguration()?->getNavigationLabel()
            ?? (config('filament-acl.resources.permissions.navigation_label') ?: __('filament-acl::filament-acl.resources.permissions.navigation_label'));
    }

    public static function getNavigationIcon(): string | BackedEnum | Htmlable | null
    {
        return static::getPermissionResourceConfiguration()?->getNavigationIcon()
            ?? config('filament-acl.resources.permissions.navigation_icon')
            ?? 'heroicon-o-lock-closed';
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return static::getPermissionResourceConfiguration()?->getNavigationGroup()
            ?? config('filament-acl.resources.permissions.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        /** @var int|null $navigationSort */
        $navigationSort = static::getPermissionResourceConfiguration()?->getNavigationSort()
            ?? config('filament-acl.resources.permissions.navigation_sort');

        return $navigationSort;
    }

    public static function getModelLabel(): string
    {
        return static::getPermissionResourceConfiguration()?->getModelLabel()
            ?? (config('filament-acl.resources.permissions.model_label') ?: __('filament-acl::filament-acl.resources.permissions.model_label'));
    }

    public static function getPluralModelLabel(): string
    {
        return static::getPermissionResourceConfiguration()?->getPluralModelLabel()
            ?? (config('filament-acl.resources.permissions.plural_model_label') ?: __('filament-acl::filament-acl.resources.permissions.plural_model_label'));
    }

    public static function getCluster(): ?string
    {
        /** @var class-string<Cluster>|null $cluster */
        $cluster = static::getPermissionResourceConfiguration()?->getCluster()
            ?? config('filament-acl.resources.permissions.cluster');

        return $cluster;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    protected static function getPermissionResourceConfiguration(): ?PermissionResourceConfiguration
    {
        if ((Filament::getCurrentPanel() === null) || (Filament::getCurrentResourceConfigurationKey() === null)) {
            return null;
        }

        $configuration = static::getConfiguration();

        if (! $configuration instanceof PermissionResourceConfiguration) {
            return null;
        }

        return $configuration;
    }

    protected static function getDefaultGuardName(): string
    {
        return static::getManagedPanel()?->getAuthGuard() ?? config('auth.defaults.guard', 'web');
    }

    protected static function shouldScopeRolesToCurrentPanel(): bool
    {
        return app(FilamentPermissionManager::class)->scopesRolesByPanel(static::getManagedPanelId());
    }

    protected static function shouldScopePermissionsToCurrentPanel(): bool
    {
        return app(FilamentPermissionManager::class)->scopesPermissionsByPanel(static::getManagedPanelId());
    }

    public static function getManagedPermissionPanel(): string | BackedEnum | null
    {
        return static::getPermissionResourceConfiguration()?->getManagedPanel()
            ?? config('filament-acl.resources.permissions.managed_panel')
            ?? Filament::getCurrentPanel()?->getId();
    }

    public static function getPermissionPanelScope(): string | BackedEnum | null
    {
        return static::getManagedPermissionPanel();
    }

    protected static function getManagedPanelId(): ?string
    {
        return Utils::normalizePanelId(static::getManagedPermissionPanel());
    }

    protected static function getManagedPanel(): ?Panel
    {
        $managedPanelId = static::getManagedPanelId();

        return $managedPanelId !== null
            ? Filament::getPanel($managedPanelId)
            : Filament::getCurrentPanel();
    }

    protected static function getPanelColumnName(): string
    {
        return (string) config('filament-acl.database.panel_scope.column', 'panel');
    }

    protected static function resolveCurrentPanelScopeValue(): string
    {
        return static::getManagedPanelId()
            ?? (string) config('filament-acl.database.panel_scope.default', 'global');
    }

    /**
     * @return array<int, Tab>
     */
    protected static function getPermissionManagementTabs(): array
    {
        $tabs = [];

        if (static::isPermissionTabEnabled('resources') && ($resourceSections = static::buildResourcePermissionSections())) {
            $tabs[] = Tab::make('resources')
                ->label(__('filament-acl::filament-acl.resources.permissions.tabs.resources'))
                ->badge(static fn (): int => static::countVisiblePermissionOptions($resourceSections))
                ->schema($resourceSections);
        }

        if (static::isPermissionTabEnabled('pages') && ($pageOptions = static::getPagePermissionOptions())) {
            $tabs[] = Tab::make('pages')
                ->label(__('filament-acl::filament-acl.resources.permissions.tabs.pages'))
                ->badge(static fn (): int => count($pageOptions))
                ->schema([
                    static::makePermissionCheckboxList('permission_groups.pages', $pageOptions),
                ]);
        }

        if (static::isPermissionTabEnabled('widgets') && ($widgetOptions = static::getWidgetPermissionOptions())) {
            $tabs[] = Tab::make('widgets')
                ->label(__('filament-acl::filament-acl.resources.permissions.tabs.widgets'))
                ->badge(static fn (): int => count($widgetOptions))
                ->schema([
                    static::makePermissionCheckboxList('permission_groups.widgets', $widgetOptions),
                ]);
        }

        if (static::isPermissionTabEnabled('custom_permissions') && ($customPermissionOptions = static::getCustomPermissionOptions())) {
            $tabs[] = Tab::make('custom_permissions')
                ->label(__('filament-acl::filament-acl.resources.permissions.tabs.custom_permissions'))
                ->badge(static fn (): int => count($customPermissionOptions))
                ->schema([
                    static::makePermissionCheckboxList('permission_groups.custom_permissions', $customPermissionOptions),
                ]);
        }

        if ($tabs === []) {
            $tabs[] = Tab::make('permissions')
                ->label(__('filament-acl::filament-acl.resources.permissions.fields.permissions'))
                ->schema([
                    static::makePermissionCheckboxList(
                        statePath: 'permission_groups.ungrouped',
                        options: static::getAllPermissionOptions(),
                    ),
                ]);
        }

        return $tabs;
    }

    /**
     * @return array<int, Section>
     */
    protected static function buildResourcePermissionSections(): array
    {
        $resourceTree = static::buildResourceTree(static::getDiscoverableResourceNodes());
        $sections = [];

        foreach ($resourceTree as $sectionLabel => $nodes) {
            $tabs = array_values(array_map(
                static fn (array $node): Tab => static::buildResourceNodeTab($node),
                $nodes,
            ));

            $sections[] = Section::make($sectionLabel)
                ->schema([
                    Tabs::make('resource_section_' . Str::slug($sectionLabel))
                        ->tabs($tabs),
                ])
                ->columnSpanFull()
                ->compact()
                ->collapsible()
                ->collapsed();
        }

        return $sections;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected static function buildResourceTree(array $nodes): array
    {
        $indexedNodes = [];

        foreach ($nodes as $node) {
            $nodeKey = $node['node_key'];

            $indexedNodes[$nodeKey] = [
                ...$node,
                'children' => [],
            ];
        }

        $parentByClass = [];
        $childPrefixes = [];

        foreach ($indexedNodes as $nodeKey => $node) {
            $childPrefixes[$nodeKey] = str($node['owner_class'])
                ->beforeLast('\\')
                ->append('\\Resources\\')
                ->toString();
        }

        foreach ($indexedNodes as $nodeKey => $node) {
            $bestParent = null;
            $bestPrefixLength = 0;

            foreach ($childPrefixes as $candidateNodeKey => $prefix) {
                if ($candidateNodeKey === $nodeKey) {
                    continue;
                }

                if (str_starts_with($node['owner_class'], $prefix) && (strlen($prefix) > $bestPrefixLength)) {
                    $bestParent = $candidateNodeKey;
                    $bestPrefixLength = strlen($prefix);
                }
            }

            $parentByClass[$nodeKey] = $bestParent;
        }

        foreach ($parentByClass as $nodeKey => $parentNodeKey) {
            if (($parentNodeKey === null) || ! array_key_exists($parentNodeKey, $indexedNodes)) {
                continue;
            }

            $indexedNodes[$parentNodeKey]['children'][] = &$indexedNodes[$nodeKey];
        }

        $sections = [];

        foreach ($indexedNodes as $nodeKey => $node) {
            if ($parentByClass[$nodeKey] !== null) {
                continue;
            }

            $sectionLabel = $node['section_label'];
            $sections[$sectionLabel] ??= [];
            $sections[$sectionLabel][] = $node;
        }

        ksort($sections);

        return $sections;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    protected static function buildResourceNodeTab(array $node): Tab
    {
        $childTabs = array_values(array_map(
            static fn (array $childNode): Tab => static::buildResourceNodeTab($childNode),
            $node['children'],
        ));

        foreach ($node['relation_managers'] as $relationManager) {
            $childTabs[] = Tab::make($relationManager['label'])
                ->schema([
                    static::makePermissionCheckboxList(
                        statePath: $relationManager['state_path'],
                        options: $relationManager['options'],
                    ),
                ]);
        }

        return [
            Tab::make($node['label'])
                ->schema(array_values(array_filter([
                    Fieldset::make(__('filament-acl::filament-acl.resources.permissions.fields.permissions'))
                        ->schema([
                            static::makePermissionCheckboxList(
                                statePath: $node['state_path'],
                                options: $node['options'],
                            ),
                        ])
                        ->columnSpanFull(),
                    $childTabs !== []
                        ? Tabs::make('resource_children_' . Str::slug($node['label']) . '_' . substr(md5($node['owner_class']), 0, 8))
                            ->tabs($childTabs)
                            ->columnSpanFull()
                        : null,
                ]))),
        ][0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function getDiscoverableResourceNodes(): array
    {
        $panel = static::getManagedPanel();

        if (! $panel instanceof Panel) {
            return [];
        }

        $nodes = [];

        foreach (static::ownerDiscovery()->discoverResources($panel) as $resourceRegistration) {
            $options = static::getOwnerPermissionOptions(
                ownerRegistration: $resourceRegistration,
            );

            if ($options === []) {
                continue;
            }

            $nodes[] = [
                'node_key' => $resourceRegistration->uniqueKey(),
                'owner_class' => $resourceRegistration->ownerClass,
                'registration_key' => $resourceRegistration->registrationKey,
                'label' => $resourceRegistration->label ?? static::resolveOwnerLabel($resourceRegistration),
                'section_label' => $resourceRegistration->sectionLabel ?? static::resolveResourceSectionLabel($resourceRegistration->ownerClass),
                'state_path' => static::makePermissionStatePath(
                    'resources',
                    $resourceRegistration->ownerClass,
                    $resourceRegistration->registrationKey,
                ),
                'options' => $options,
                'relation_managers' => static::getRelationManagerNodes($resourceRegistration),
            ];
        }

        return $nodes;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function getRelationManagerNodes(PermissionOwnerRegistration $resourceRegistration): array
    {
        $panel = static::getManagedPanel();

        if (! $panel instanceof Panel) {
            return [];
        }

        $nodes = [];

        foreach (static::ownerDiscovery()->discoverRelationManagers($panel, $resourceRegistration) as $relationManagerRegistration) {
            $options = static::getOwnerPermissionOptions(
                ownerRegistration: $relationManagerRegistration,
            );

            if ($options === []) {
                continue;
            }

            $nodes[] = [
                'owner_class' => $relationManagerRegistration->ownerClass,
                'registration_key' => $relationManagerRegistration->registrationKey,
                'label' => $relationManagerRegistration->label ?? static::resolveOwnerLabel($relationManagerRegistration),
                'state_path' => static::makePermissionStatePath(
                    'relation_managers',
                    $relationManagerRegistration->ownerClass,
                    $relationManagerRegistration->registrationKey,
                ),
                'options' => $options,
            ];
        }

        return $nodes;
    }

    protected static function resolveResourceSectionLabel(string $resourceClass): string
    {
        $cluster = $resourceClass::getCluster();

        if (($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
            return $cluster::getNavigationLabel();
        }

        $navigationGroup = $resourceClass::getNavigationGroup();

        return (string) match (true) {
            $navigationGroup instanceof BackedEnum => $navigationGroup->value,
            $navigationGroup instanceof UnitEnum => $navigationGroup->name,
            is_string($navigationGroup) => $navigationGroup,
            default => __('filament-acl::filament-acl.resources.permissions.groups.resources'),
        };
    }

    protected static function makePermissionCheckboxList(string $statePath, array $options): CheckboxList
    {
        return CheckboxList::make($statePath)
            ->hiddenLabel()
            ->options($options)
            ->bulkToggleable()
            ->searchable()
            ->columns(2)
            ->columnSpanFull();
    }

    protected static function getOwnerPermissionOptions(
        PermissionOwnerRegistration $ownerRegistration,
    ): array {
        $permissionNames = [];

        foreach (static::resolveOwnerAbilities($ownerRegistration) as $ability) {
            $permissionNames[$ability] = app(FilamentPermissionManager::class)->defaultPermissionKeyBuilder(
                $ability,
                static::resolveOwnerSubject($ownerRegistration),
            );
        }

        if ($permissionNames === []) {
            return [];
        }

        $permissionModel = app(StoresPermissions::class)->getPermissionModel();
        $query = $permissionModel::query()
            ->whereIn('name', array_values($permissionNames))
            ->orderBy('name');

        if (static::shouldScopePermissionsToCurrentPanel()) {
            $query->where(static::getPanelColumnName(), static::resolveCurrentPanelScopeValue());
        }

        /** @var array<int|string, string> $options */
        $options = [];

        foreach ($query->get() as $permission) {
            /** @var Model $permission */
            $permissionName = (string) $permission->getAttribute('name');
            $ability = array_search($permissionName, $permissionNames, true);

            if (! is_string($ability)) {
                continue;
            }

            $options[$permission->getKey()] = Str::of($ability)->headline()->toString();
        }

        return $options;
    }

    protected static function resolveOwnerAbilities(PermissionOwnerRegistration $ownerRegistration): array
    {
        if (method_exists($ownerRegistration->ownerClass, 'getPermissionActions')) {
            /** @var array<int, string> $actions */
            $actions = static::withOwnerConfigurationContext(
                $ownerRegistration,
                static fn (): array => $ownerRegistration->ownerClass::getPermissionActions(),
            );

            return $actions;
        }

        return match ($ownerRegistration->ownerType) {
            PermissionEntityType::Page => app(DefaultPermissionActionRegistry::class)->forPage(),
            PermissionEntityType::Widget => app(DefaultPermissionActionRegistry::class)->forWidget(),
            default => [],
        };
    }

    protected static function resolveOwnerSubject(PermissionOwnerRegistration $ownerRegistration): string
    {
        return app(ResolvesPermissionSubject::class)->resolve(
            entityClass: $ownerRegistration->ownerClass,
            entityType: $ownerRegistration->ownerType,
            panelId: static::getManagedPanelId(),
            registrationKey: $ownerRegistration->registrationKey,
            meta: array_filter([
                ...$ownerRegistration->meta,
                'related_resource' => $ownerRegistration->relatedResource,
            ], static fn (mixed $value): bool => filled($value)),
        );
    }

    /**
     * @return array<int|string, string>
     */
    protected static function getPagePermissionOptions(): array
    {
        $panel = static::getManagedPanel();

        if (! $panel instanceof Panel) {
            return [];
        }

        $options = [];

        foreach (static::ownerDiscovery()->discoverPages($panel) as $pageRegistration) {
            if (in_array($pageRegistration->ownerClass, config('filament-acl.pages.exclude', []), true)) {
                continue;
            }

            $options += static::getSingularOwnerPermissionOption(
                ownerRegistration: $pageRegistration,
                label: $pageRegistration->label ?? static::resolveOwnerLabel($pageRegistration),
            );
        }

        return $options;
    }

    /**
     * @return array<int|string, string>
     */
    protected static function getWidgetPermissionOptions(): array
    {
        $panel = static::getManagedPanel();

        if (! $panel instanceof Panel) {
            return [];
        }

        $options = [];

        foreach (static::ownerDiscovery()->discoverWidgets($panel) as $widgetRegistration) {
            if (in_array($widgetRegistration->ownerClass, config('filament-acl.widgets.exclude', []), true)) {
                continue;
            }

            $options += static::getSingularOwnerPermissionOption(
                ownerRegistration: $widgetRegistration,
                label: $widgetRegistration->label ?? static::resolveOwnerLabel($widgetRegistration),
            );
        }

        return $options;
    }

    /**
     * @return array<int|string, string>
     */
    protected static function getCustomPermissionOptions(): array
    {
        $customPermissions = Utils::resolveCustomPermissions(static::getManagedPanelId());

        if ($customPermissions === []) {
            return [];
        }

        $permissionModel = app(StoresPermissions::class)->getPermissionModel();
        $query = $permissionModel::query()->whereIn('name', array_keys($customPermissions));

        if (static::shouldScopePermissionsToCurrentPanel()) {
            $query->where(static::getPanelColumnName(), static::resolveCurrentPanelScopeValue());
        }

        /** @var array<int|string, string> $options */
        $options = [];

        foreach ($query->get() as $permission) {
            /** @var Model $permission */
            $name = (string) $permission->getAttribute('name');
            $label = $customPermissions[$name] ?? $name;
            $options[$permission->getKey()] = $label;
        }

        asort($options);

        return $options;
    }

    protected static function getSingularOwnerPermissionOption(
        PermissionOwnerRegistration $ownerRegistration,
        string $label,
    ): array {
        $permissionNames = static::resolveOwnerAbilities($ownerRegistration);

        if ($permissionNames === []) {
            return [];
        }

        $permissionKey = app(FilamentPermissionManager::class)->defaultPermissionKeyBuilder(
            $permissionNames[0],
            static::resolveOwnerSubject($ownerRegistration),
        );
        $permissionModel = app(StoresPermissions::class)->getPermissionModel();
        $query = $permissionModel::query()->where('name', $permissionKey);

        if (static::shouldScopePermissionsToCurrentPanel()) {
            $query->where(static::getPanelColumnName(), static::resolveCurrentPanelScopeValue());
        }

        /** @var ?Model $permission */
        $permission = $query->first();

        if (! $permission instanceof Model) {
            return [];
        }

        return [
            $permission->getKey() => $label,
        ];
    }

    /**
     * @return array<int|string, string>
     */
    protected static function getAllPermissionOptions(): array
    {
        $permissionModel = app(StoresPermissions::class)->getPermissionModel();
        $query = $permissionModel::query()->orderBy('name');

        if (static::shouldScopePermissionsToCurrentPanel()) {
            $query->where(static::getPanelColumnName(), static::resolveCurrentPanelScopeValue());
        }

        $options = [];

        foreach ($query->get() as $permission) {
            /** @var Model $permission */
            $permissionName = (string) $permission->getAttribute('name');
            $options[$permission->getKey()] = static::formatPermissionOptionLabel($permissionName);
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getPermissionFieldDefinitions(): array
    {
        $definitions = [];

        foreach (static::getDiscoverableResourceNodes() as $node) {
            $definitions[$node['state_path']] = array_keys($node['options']);

            foreach ($node['relation_managers'] as $relationManager) {
                $definitions[$relationManager['state_path']] = array_keys($relationManager['options']);
            }
        }

        if ($pageOptions = static::getPagePermissionOptions()) {
            $definitions['permission_groups.pages'] = array_keys($pageOptions);
        }

        if ($widgetOptions = static::getWidgetPermissionOptions()) {
            $definitions['permission_groups.widgets'] = array_keys($widgetOptions);
        }

        if ($customPermissionOptions = static::getCustomPermissionOptions()) {
            $definitions['permission_groups.custom_permissions'] = array_keys($customPermissionOptions);
        }

        if ($definitions === []) {
            $definitions['permission_groups.ungrouped'] = array_keys(static::getAllPermissionOptions());
        }

        return $definitions;
    }

    /**
     * @param  array<int, int|string>  $assignedPermissionIds
     * @return array<string, mixed>
     */
    public static function fillPermissionGroupState(array $assignedPermissionIds): array
    {
        $state = [];

        foreach (static::getPermissionFieldDefinitions() as $statePath => $permissionIds) {
            data_set(
                $state,
                $statePath,
                array_values(array_intersect($assignedPermissionIds, $permissionIds)),
            );
        }

        return $state;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, int|string>
     */
    public static function extractPermissionIdsFromData(array $data): array
    {
        $groups = data_get($data, 'permission_groups', []);

        if (! is_array($groups)) {
            return [];
        }

        $flatten = static function (array $values) use (&$flatten): array {
            $permissionIds = [];

            foreach ($values as $value) {
                if (is_array($value)) {
                    $permissionIds = [
                        ...$permissionIds,
                        ...$flatten($value),
                    ];

                    continue;
                }

                if (is_int($value) || is_string($value)) {
                    $permissionIds[] = $value;
                }
            }

            return $permissionIds;
        };

        return array_values(array_unique($flatten($groups)));
    }

    protected static function makePermissionStatePath(string $group, string $ownerClass, ?string $registrationKey = null): string
    {
        return sprintf(
            'permission_groups.%s.%s',
            $group,
            substr(md5($ownerClass . '|' . ($registrationKey ?? 'default')), 0, 16),
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected static function parsePermissionName(string $permissionName): array
    {
        $separator = (string) config('filament-acl.permissions.separator', ':');

        if (! str_contains($permissionName, $separator)) {
            return [$permissionName, __('filament-acl::filament-acl.resources.permissions.groups.ungrouped')];
        }

        $segments = explode($separator, $permissionName, 2);

        return [
            $segments[0],
            $segments[1],
        ];
    }

    protected static function formatPermissionOptionLabel(string $permissionName): string
    {
        [$ability, $subject] = static::parsePermissionName($permissionName);

        return sprintf(
            '%s - %s',
            Str::of($subject)->headline()->toString(),
            Str::of($ability)->headline()->toString(),
        );
    }

    protected static function resolveOwnerLabel(PermissionOwnerRegistration $ownerRegistration): string
    {
        return match ($ownerRegistration->ownerType) {
            PermissionEntityType::Resource => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'Resource')),
            PermissionEntityType::RelationManager => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'RelationManager')),
            PermissionEntityType::Page => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'Page')),
            PermissionEntityType::Widget => Str::headline(Str::beforeLast(class_basename($ownerRegistration->ownerClass), 'Widget')),
            default => Str::headline(class_basename($ownerRegistration->ownerClass)),
        };
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected static function withOwnerConfigurationContext(
        PermissionOwnerRegistration $ownerRegistration,
        callable $callback,
    ): mixed {
        $previousPanel = Filament::getCurrentPanel();
        $previousResourceConfigurationKey = Filament::getCurrentResourceConfigurationKey();
        $previousPageConfigurationKey = Filament::getCurrentPageConfigurationKey();

        if ($ownerRegistration->panelId !== null) {
            Filament::setCurrentPanel(Filament::getPanel($ownerRegistration->panelId));
        }

        if (in_array($ownerRegistration->ownerType, [PermissionEntityType::Resource, PermissionEntityType::RelationManager], true)) {
            Filament::setCurrentResourceConfigurationKey($ownerRegistration->registrationKey);
        }

        if ($ownerRegistration->ownerType === PermissionEntityType::Page) {
            Filament::setCurrentPageConfigurationKey($ownerRegistration->registrationKey);
        }

        try {
            return $callback();
        } finally {
            Filament::setCurrentPanel($previousPanel);
            Filament::setCurrentResourceConfigurationKey($previousResourceConfigurationKey);
            Filament::setCurrentPageConfigurationKey($previousPageConfigurationKey);
        }
    }

    protected static function ownerDiscovery(): PermissionOwnerDiscovery
    {
        return app(PermissionOwnerDiscovery::class);
    }

    /**
     * @param  array<int, Section>  $sections
     */
    protected static function countVisiblePermissionOptions(array $sections): int
    {
        $count = 0;

        foreach (static::getDiscoverableResourceNodes() as $node) {
            $count += count($node['options']);

            foreach ($node['relation_managers'] as $relationManager) {
                $count += count($relationManager['options']);
            }
        }

        return $count;
    }

    protected static function isPermissionTabEnabled(string $tab): bool
    {
        return (bool) config("filament-acl.resources.permissions.tabs.{$tab}", true);
    }
}
