<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Permissions;

use BackedEnum;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentAclPlugin;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Resources\Concerns\HasResourcePermissions;
use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Contracts\HasIcon;
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
     * @return array<int, string>
     */
    public static function getPermissionActions(): array
    {
        /** @var array<int, string> $actions */
        $actions = config('filament-acl.resources.permissions.actions', [
            'viewAny',
            'create',
            'update',
            'delete',
        ]);

        return $actions;
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
            static::makePermissionFormGrid(),
        ]);
    }

    protected static function makePermissionFormGrid(): Grid
    {
        return Grid::make()
            ->schema([
                static::makePermissionDetailsSection(),
                static::makePermissionTabsComponent(),
            ])
            ->columnSpanFull();
    }

    protected static function makePermissionDetailsSection(): Section
    {
        return Section::make()
            ->schema([
                static::makeNameInput(),
                static::makeSelectAllToggle(),
                static::makeGuardNameHidden(),
                static::makePanelScopeHidden(),
            ])
            ->columns(2)
            ->columnSpanFull();
    }

    protected static function makePermissionTabsComponent(): Tabs
    {
        return Tabs::make('permission_groups_tabs')
            ->tabs(static::getPermissionManagementTabs())
            ->columnSpanFull();
    }

    protected static function makeNameInput(): TextInput
    {
        return TextInput::make('name')
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
            );
    }

    protected static function makeSelectAllToggle(): Toggle
    {
        return Toggle::make('select_all')
            ->label(__('filament-acl::filament-acl.resources.permissions.fields.select_all'))
            ->helperText(__('filament-acl::filament-acl.resources.permissions.fields.select_all_help'))
            ->onIcon('heroicon-s-shield-check')
            ->offIcon('heroicon-s-shield-exclamation')
            ->afterStateUpdatedJs(static fn (): string => static::buildSelectAllJs())
            ->dehydrated(false);
    }

    /**
     * Build a JavaScript expression that toggles all permission checkboxes based on master toggle state.
     *
     * Uses a guard variable to prevent cascading: when section toggle actions
     * programmatically set `select_all`, this callback is suppressed to avoid
     * clearing permissions across all sections.
     */
    protected static function buildSelectAllJs(): string
    {
        $allFieldDefs = static::getPermissionFieldDefinitions();
        $statements = [];

        foreach ($allFieldDefs as $statePath => $optionKeys) {
            $encoded = json_encode(array_values($optionKeys), JSON_THROW_ON_ERROR);
            $statements[] = "\$set('{$statePath}', \$state ? {$encoded} : [])";
        }

        $setStatements = implode(";\n", $statements);
        $guardCondition = static::buildJsMasterToggleGuardCondition();
        $guardEnter = static::buildJsMasterToggleGuardEnter();
        $guardLeave = static::buildJsMasterToggleGuardLeave();
        $bulkSync = static::buildJsBulkToggleSync(afterSync: $guardLeave);

        return <<<JS
        if ({$guardCondition}) {
            // Skip cascade: this was triggered programmatically by a CheckboxList or
            // section action — not by the user clicking the master toggle.
        } else {
            {$guardEnter};
            {$setStatements};
            {$bulkSync}
        }
        JS;
    }

    protected static function makeGuardNameHidden(): Hidden
    {
        return Hidden::make('guard_name')
            ->default(static::getDefaultGuardName());
    }

    protected static function makePanelScopeHidden(): Hidden
    {
        return Hidden::make(static::getPanelColumnName())
            ->default(static::resolveCurrentPanelScopeValue())
            ->dehydrated(static::shouldScopeRolesToCurrentPanel());
    }

    public static function table(Table $table): Table
    {
        $table
            ->deferLoading()
            ->columns(static::getPermissionTableColumns())
            ->recordUrl(null)
            ->recordActions(static::getPermissionTableRecordActions());

        $configureUsing = static::getPluginTableConfigurator();

        if ($configureUsing !== null) {
            $configureUsing($table);
        }

        return $table;
    }

    /**
     * @return array<int, TextColumn>
     */
    protected static function getPermissionTableColumns(): array
    {
        return [
            static::makePermissionNameColumn(),
            static::makePermissionGuardNameColumn(),
            static::makePermissionPanelColumn(),
            static::makePermissionCountColumn(),
            static::makePermissionUpdatedAtColumn(),
        ];
    }

    protected static function makePermissionNameColumn(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('filament-acl::filament-acl.resources.permissions.columns.name'))
            ->weight('font-medium')
            ->formatStateUsing(static fn (string $state): string => Str::headline($state))
            ->searchable();
    }

    protected static function makePermissionGuardNameColumn(): TextColumn
    {
        return TextColumn::make('guard_name')
            ->label(__('filament-acl::filament-acl.resources.permissions.columns.guard_name'))
            ->badge()
            ->searchable();
    }

    protected static function makePermissionPanelColumn(): TextColumn
    {
        return TextColumn::make(static::getPanelColumnName())
            ->label(__('filament-acl::filament-acl.resources.permissions.columns.panel'))
            ->badge()
            ->visible(static fn (): bool => static::shouldScopeRolesToCurrentPanel());
    }

    protected static function makePermissionCountColumn(): TextColumn
    {
        return TextColumn::make('permissions_count')
            ->label(__('filament-acl::filament-acl.resources.permissions.columns.permissions_count'))
            ->counts('permissions')
            ->badge()
            ->color('success')
            ->sortable();
    }

    protected static function makePermissionUpdatedAtColumn(): TextColumn
    {
        return TextColumn::make('updated_at')
            ->label(__('filament-acl::filament-acl.resources.permissions.columns.updated_at'))
            ->since()
            ->dateTimeTooltip('Y-m-d H:i:s')
            ->sortable();
    }

    /**
     * @return array<int, Action>
     */
    protected static function getPermissionTableRecordActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }

    protected static function getPluginTableConfigurator(): ?\Closure
    {
        try {
            return FilamentAclPlugin::get()->getConfigurePermissionsTableUsing();
        } catch (\Throwable) {
            return null;
        }
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
     * Build permission sections grouped by navigation group / cluster.
     *
     * Hierarchy rules:
     * - NavGroup/Cluster sections: ALWAYS use Tabs for resources, even with single resource
     * - Standalone sections (no cluster, no navGroup): Fieldset + child Tabs directly in Section
     * - afterHeader toggle action on every Section
     *
     * @return array<int, Section>
     */
    protected static function buildResourcePermissionSections(): array
    {
        $resourceTree = static::buildResourceTree(static::getDiscoverableResourceNodes());
        $sections = [];

        foreach ($resourceTree as $sectionLabel => $nodes) {
            $sectionIcon = static::resolveResourceSectionIcon($nodes);
            $statePathsWithOptions = static::collectAllNodeStatePathsWithOptions($nodes);
            $sectionId = Str::slug($sectionLabel) . '_' . substr(md5($sectionLabel), 0, 8);

            $isStandalone = static::isSectionStandaloneByLabel($nodes);

            if ($isStandalone && count($nodes) === 1) {
                $singleNode = $nodes[0];

                $schema = static::buildStandaloneNodeSchema($singleNode);
            } else {
                $tabs = [];

                foreach ($nodes as $node) {
                    $tabs[] = static::buildResourceGroupTab($node);
                }

                $schema = [
                    static::makeInnerTabs('section_' . $sectionId, $tabs),
                ];
            }

            $totalPermissions = 0;

            foreach ($nodes as $node) {
                $totalPermissions += static::countNodePermissions($node);
            }

            $section = Section::make($sectionLabel)
                ->description(trans_choice(
                    'filament-acl::filament-acl.resources.permissions.section_description',
                    $totalPermissions,
                    ['count' => $totalPermissions],
                ))
                ->schema($schema)
                ->columnSpanFull()
                ->compact()
                ->collapsible()
                ->collapsed(static::getPluginOption('usesSectionsCollapsed', false))
                ->persistCollapsed(static::getPluginOption('usesSectionsPersistCollapsed', true))
                ->afterHeader(
                    fn (): array => static::buildGroupToggleActions('section_' . $sectionId, $statePathsWithOptions),
                );

            if ($sectionIcon !== null) {
                $section->icon($sectionIcon);
            }

            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Determine if a section's nodes belong to standalone resources.
     *
     * A section is standalone when every node's section_label equals its own label,
     * meaning Discovery did not group it under a cluster or navigation group.
     * This avoids re-querying cluster/navGroup without panel context.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     */
    protected static function isSectionStandaloneByLabel(array $nodes): bool
    {
        $firstNode = $nodes[0] ?? null;

        if ($firstNode === null) {
            return true;
        }

        return ($firstNode['section_label'] ?? '') === ($firstNode['label'] ?? '');
    }

    /**
     * Build the schema for a standalone resource node (direct content, no Tab wrapper).
     *
     * @param  array<string, mixed>  $node
     * @return array<int, Component>
     */
    protected static function buildStandaloneNodeSchema(array $node): array
    {
        $children = $node['children'] ?? [];

        $childTabs = array_values(array_map(
            static fn (array $childNode): Tab => static::buildResourceNodeTab($childNode),
            $children,
        ));

        foreach ($node['relation_managers'] as $relationManager) {
            $childTabs[] = static::buildRelationManagerTab($relationManager);
        }

        $schema = [];

        if (! empty($children) || ! empty($node['relation_managers'])) {
            $schema[] = Fieldset::make(__('filament-acl::filament-acl.resources.permissions.fields.permissions'))
                ->schema([
                    static::makePermissionCheckboxList(
                        statePath: $node['state_path'],
                        options: $node['options'],
                    ),
                ])
                ->columnSpanFull();

            $schema[] = static::makeInnerTabs('children_' . Str::slug($node['label']) . '_' . substr(md5($node['owner_class']), 0, 8), $childTabs);
        } else {
            $schema[] = static::makePermissionCheckboxList(
                statePath: $node['state_path'],
                options: $node['options'],
            );
        }

        return $schema;
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
     * Build a Tab for a root resource node inside a grouped section.
     *
     * When the resource has children or relation managers, includes a toggle
     * action and nested Tabs. Otherwise, just the CheckboxList.
     *
     * @param  array<string, mixed>  $node
     */
    protected static function buildResourceGroupTab(array $node): Tab
    {
        $permissionCount = static::countNodePermissions($node);
        $children = $node['children'] ?? [];

        $childTabs = array_values(array_map(
            static fn (array $childNode): Tab => static::buildResourceNodeTab($childNode),
            $children,
        ));

        foreach ($node['relation_managers'] as $relationManager) {
            $childTabs[] = static::buildRelationManagerTab($relationManager);
        }

        $hasNested = $childTabs !== [];

        if ($hasNested) {
            $statePathsWithOptions = static::collectAllNodeStatePathsWithOptions([$node]);
            $uniqueId = Str::slug($node['label']) . '_' . substr(md5($node['owner_class']), 0, 8);

            $schema = [
                ...static::buildGroupToggleActions('resource_' . $uniqueId, $statePathsWithOptions),

                Fieldset::make(__('filament-acl::filament-acl.resources.permissions.fields.permissions'))
                    ->schema([
                        static::makePermissionCheckboxList(
                            statePath: $node['state_path'],
                            options: $node['options'],
                        ),
                    ])
                    ->columnSpanFull(),

                static::makeInnerTabs('resource_children_' . $uniqueId, $childTabs),
            ];
        } else {
            $schema = [
                static::makePermissionCheckboxList(
                    statePath: $node['state_path'],
                    options: $node['options'],
                ),
            ];
        }

        $tab = Tab::make($node['label'])
            ->badge($permissionCount > 0 ? $permissionCount : null)
            ->schema($schema);

        if (isset($node['icon'])) {
            $tab->icon($node['icon']);
        }

        return $tab;
    }

    /**
     * Build a Tab for a child resource node (recursive).
     *
     * When the node has children/RMs, includes a toggle action and nested Tabs.
     *
     * @param  array<string, mixed>  $node
     */
    protected static function buildResourceNodeTab(array $node): Tab
    {
        $children = $node['children'] ?? [];

        $childTabs = array_values(array_map(
            static fn (array $childNode): Tab => static::buildResourceNodeTab($childNode),
            $children,
        ));

        foreach ($node['relation_managers'] as $relationManager) {
            $childTabs[] = static::buildRelationManagerTab($relationManager);
        }

        $hasNested = $childTabs !== [];

        if ($hasNested) {
            $statePathsWithOptions = static::collectAllNodeStatePathsWithOptions([$node]);
            $uniqueId = Str::slug($node['label']) . '_' . substr(md5($node['owner_class']), 0, 8);

            $schema = [
                ...static::buildGroupToggleActions('node_' . $uniqueId, $statePathsWithOptions),

                Fieldset::make(__('filament-acl::filament-acl.resources.permissions.fields.permissions'))
                    ->schema([
                        static::makePermissionCheckboxList(
                            statePath: $node['state_path'],
                            options: $node['options'],
                        ),
                    ])
                    ->columnSpanFull(),

                static::makeInnerTabs('resource_children_' . $uniqueId, $childTabs),
            ];
        } else {
            $schema = [
                static::makePermissionCheckboxList(
                    statePath: $node['state_path'],
                    options: $node['options'],
                ),
            ];
        }

        $tab = Tab::make($node['label'])
            ->badge(fn (): ?int => ($count = static::countNodePermissions($node)) > 0 ? $count : null)
            ->schema($schema);

        if (isset($node['icon'])) {
            $tab->icon($node['icon']);
        }

        return $tab;
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
            /** @var class-string<resource> $resourceOwnerClass */
            $resourceOwnerClass = $resourceRegistration->ownerClass;
            $options = static::getOwnerPermissionOptions(
                ownerRegistration: $resourceRegistration,
            );

            if ($options === []) {
                continue;
            }

            $nodes[] = [
                'node_key' => $resourceRegistration->uniqueKey(),
                'owner_class' => $resourceOwnerClass,
                'registration_key' => $resourceRegistration->registrationKey,
                'label' => $resourceRegistration->label ?? static::resolveOwnerLabel($resourceRegistration),
                'icon' => static::resolveResourceNodeIcon($resourceOwnerClass),
                'section_label' => $resourceRegistration->sectionLabel ?? $resourceRegistration->label ?? static::resolveOwnerLabel($resourceRegistration),
                'state_path' => static::makePermissionStatePath(
                    'resources',
                    $resourceOwnerClass,
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
                'icon' => $relationManagerRegistration->meta['icon'] ?? null,
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

    /**
     * @param  array<string, mixed>  $relationManager
     */
    protected static function buildRelationManagerTab(array $relationManager): Tab
    {
        $permissionCount = count($relationManager['options']);

        $tab = Tab::make($relationManager['label'])
            ->badge($permissionCount > 0 ? $permissionCount : null)
            ->schema([
                static::makePermissionCheckboxList(
                    statePath: $relationManager['state_path'],
                    options: $relationManager['options'],
                ),
            ]);

        if (($relationManager['icon'] ?? null) !== null) {
            $tab->icon($relationManager['icon']);
        }

        return $tab;
    }

    /**
     * Resolve a plugin option via the fluent API, falling back to config when the plugin is not registered.
     */
    protected static function getPluginOption(string $getter, mixed $default): mixed
    {
        try {
            return FilamentAclPlugin::get()->{$getter}();
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Resolve the icon for a permission section based on the first resource in the group.
     *
     * Checks: Cluster icon > Navigation group enum HasIcon > null.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     */
    protected static function resolveResourceSectionIcon(array $nodes): string | BackedEnum | Htmlable | null
    {
        $firstNode = $nodes[0] ?? null;

        if ($firstNode === null) {
            return null;
        }

        $resourceClass = $firstNode['owner_class'];

        $cluster = $resourceClass::getCluster();

        if (($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
            return $cluster::getNavigationIcon();
        }

        $navigationGroup = $resourceClass::getNavigationGroup();

        if ($navigationGroup instanceof HasIcon) {
            return $navigationGroup->getIcon();
        }

        return $resourceClass::getNavigationIcon();
    }

    /**
     * Resolve the icon for a resource node tab.
     *
     * @param  class-string<resource>  $resourceClass
     */
    protected static function resolveResourceNodeIcon(string $resourceClass): string | BackedEnum | Htmlable | null
    {
        return $resourceClass::getNavigationIcon();
    }

    /**
     * @param  array<int|string, string>  $options
     */
    protected static function makePermissionCheckboxList(string $statePath, array $options): CheckboxList
    {
        return CheckboxList::make($statePath)
            ->hiddenLabel()
            ->options($options)
            ->bulkToggleable()
            ->columns(2)
            ->columnSpanFull()
            ->afterStateUpdatedJs(static::buildCheckboxListMasterSyncJs());
    }

    /**
     * Create a Tabs component for inner (nested) content, applying the configured style.
     *
     * @param  array<Tab>  $tabs
     */
    protected static function makeInnerTabs(string $name, array $tabs): Tabs
    {
        $innerTabs = Tabs::make($name)
            ->tabs($tabs)
            ->columnSpanFull();

        if (static::getPluginOption('usesInnerTabsVertical', false)) {
            $innerTabs->vertical();
        }

        $innerTabs->contained(static::getPluginOption('usesInnerTabsContained', false));

        return $innerTabs;
    }

    // ─── Toggle Actions ─────────────────────────────────────────

    /**
     * Build toggle actions that select/deselect all permissions using client-side JavaScript.
     *
     * Returns two Actions components (schema-level): "select all" (visible when not all
     * selected) and "deselect all" (visible when all selected), both using actionJs()
     * for zero server requests. visibleJs() is on the Actions wrapper (schema component),
     * not on Action itself.
     *
     * @param  array<string, array<int, int|string>>  $statePathsWithOptions
     * @return array<int, Actions>
     */
    protected static function buildGroupToggleActions(string $id, array $statePathsWithOptions): array
    {
        $allSelectedCondition = static::buildJsAllSelectedCondition($statePathsWithOptions);
        $selectAllJs = static::buildJsSetState($statePathsWithOptions, selectAll: true);
        $deselectAllJs = static::buildJsSetState($statePathsWithOptions, selectAll: false);
        $masterSyncJs = static::buildJsMasterToggleSync();
        $guardEnter = static::buildJsMasterToggleGuardEnter();
        $guardLeave = static::buildJsMasterToggleGuardLeave();
        $bulkToggleSync = static::buildJsBulkToggleSync(afterSync: $guardLeave);
        $selectAllActionJs = <<<JS
        {$guardEnter};
        {$selectAllJs};
        {$masterSyncJs};
        {$bulkToggleSync}
        JS;
        $deselectAllActionJs = <<<JS
        {$guardEnter};
        {$deselectAllJs};
        \$set('select_all', false);
        {$bulkToggleSync}
        JS;

        return [
            Actions::make([
                Action::make('select_all_' . $id)
                    ->label(__('filament-acl::filament-acl.resources.permissions.section_toggle.select_all'))
                    ->color('primary')
                    ->link()
                    ->actionJs($selectAllActionJs),
            ])->visibleJs("!({$allSelectedCondition})"),

            Actions::make([
                Action::make('deselect_all_' . $id)
                    ->label(__('filament-acl::filament-acl.resources.permissions.section_toggle.deselect_all'))
                    ->color('primary')
                    ->link()
                    ->actionJs($deselectAllActionJs),
            ])->visibleJs($allSelectedCondition),
        ];
    }

    /**
     * Build a JavaScript expression that evaluates to true when all checkboxes in the given state paths are selected.
     *
     * @param  array<string, array<int, int|string>>  $statePathsWithOptions
     */
    protected static function buildJsAllSelectedCondition(array $statePathsWithOptions): string
    {
        $conditions = [];

        foreach ($statePathsWithOptions as $statePath => $optionKeys) {
            $count = count($optionKeys);
            $conditions[] = "(\$get('{$statePath}') ?? []).length === {$count}";
        }

        return $conditions !== [] ? implode(' && ', $conditions) : 'true';
    }

    /**
     * Build JavaScript statements to set all checkbox lists to selected or empty.
     *
     * @param  array<string, array<int, int|string>>  $statePathsWithOptions
     */
    protected static function buildJsSetState(array $statePathsWithOptions, bool $selectAll): string
    {
        $statements = [];

        foreach ($statePathsWithOptions as $statePath => $optionKeys) {
            if ($selectAll) {
                $encoded = json_encode(array_values($optionKeys), JSON_THROW_ON_ERROR);
                $statements[] = "\$set('{$statePath}', {$encoded})";
            } else {
                $statements[] = "\$set('{$statePath}', [])";
            }
        }

        return implode(";\n", $statements);
    }

    /**
     * Build a JavaScript snippet that syncs each CheckboxList's internal bulkToggleable state.
     *
     * After programmatic $set(), the Alpine `areAllCheckboxesChecked` property
     * inside each `checkboxListFormComponent` does not update automatically.
     * The $set() updates the Livewire state which then propagates to the DOM
     * via wire:model — this requires a microtask + Alpine tick to complete.
     * We use setTimeout(0) to defer until after Livewire has flushed the
     * DOM changes, then call checkIfAllCheckboxesAreChecked() via Alpine.$data().
     */
    protected static function buildJsBulkToggleSync(?string $afterSync = null): string
    {
        $afterSync ??= '';

        return <<<JS
        setTimeout(() => {
            document.querySelectorAll('.fi-fo-checkbox-list').forEach(el => {
                const data = Alpine.\$data(el);
                if (data?.checkIfAllCheckboxesAreChecked) {
                    data.checkIfAllCheckboxesAreChecked();
                }
            });
            {$afterSync}
        }, 0)
        JS;
    }

    /**
     * Build a JavaScript statement that syncs the master select_all toggle based on all permission fields.
     */
    protected static function buildJsMasterToggleSync(): string
    {
        $allFieldDefs = static::getPermissionFieldDefinitions();
        $condition = static::buildJsAllSelectedCondition($allFieldDefs);

        return <<<JS
        \$set('select_all', {$condition})
        JS;
    }

    /**
     * Build JavaScript for CheckboxList afterStateUpdatedJs that syncs the master toggle.
     *
     * Uses the guard to prevent the master toggle's own afterStateUpdatedJs from
     * cascading and clearing all permissions when select_all is set to false.
     */
    protected static function buildCheckboxListMasterSyncJs(): string
    {
        $guardEnter = static::buildJsMasterToggleGuardEnter();
        $guardLeave = static::buildJsMasterToggleGuardLeave();
        $masterSync = static::buildJsMasterToggleSync();

        return <<<JS
        {$guardEnter};
        {$masterSync};
        {$guardLeave}
        JS;
    }

    protected static function buildJsMasterToggleGuardCondition(): string
    {
        return <<<'JS'
        (window.__aclMasterToggleGuardDepth ?? 0) > 0
        JS;
    }

    protected static function buildJsMasterToggleGuardEnter(): string
    {
        return <<<'JS'
        window.__aclMasterToggleGuardDepth = (window.__aclMasterToggleGuardDepth ?? 0) + 1
        JS;
    }

    protected static function buildJsMasterToggleGuardLeave(): string
    {
        return <<<'JS'
        queueMicrotask(() => {
            requestAnimationFrame(() => {
                window.__aclMasterToggleGuardDepth = Math.max((window.__aclMasterToggleGuardDepth ?? 1) - 1, 0)
            })
        })
        JS;
    }

    /**
     * Recursively collect all state paths from nodes and their relation managers.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, string>
     */
    protected static function collectAllNodeStatePaths(array $nodes): array
    {
        return array_keys(static::collectAllNodeStatePathsWithOptions($nodes));
    }

    /**
     * Recursively collect state paths with their option keys from nodes and their relation managers.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, array<int, int|string>>
     */
    protected static function collectAllNodeStatePathsWithOptions(array $nodes): array
    {
        $map = [];

        foreach ($nodes as $node) {
            $map[$node['state_path']] = array_keys($node['options']);

            foreach ($node['relation_managers'] as $rm) {
                $map[$rm['state_path']] = array_keys($rm['options']);
            }

            if (! empty($node['children'])) {
                $map = array_merge($map, static::collectAllNodeStatePathsWithOptions($node['children']));
            }
        }

        return $map;
    }

    /**
     * @return array<int|string, string>
     */
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

            $options[$permission->getKey()] = static::resolveAbilityLabel($ability);
        }

        return $options;
    }

    /**
     * @return array<int, string>
     */
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
            $label = __($customPermissions[$name] ?? $name);
            $options[$permission->getKey()] = $label;
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<int|string, string>
     */
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
        $query = $permissionModel::query()
            ->where('name', $permissionKey); // @phpstan-ignore argument.type

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

        $normalizedAssignedPermissionIds = array_values(array_unique(array_map(
            static fn (int | string $permissionId): string => (string) $permissionId,
            $assignedPermissionIds,
        )));
        $allPermissionIds = collect(static::getPermissionFieldDefinitions())
            ->flatten()
            ->map(static fn (int | string $permissionId): string => (string) $permissionId)
            ->unique()
            ->values()
            ->all();

        $state['select_all'] = $allPermissionIds !== []
            && array_diff($allPermissionIds, $normalizedAssignedPermissionIds) === [];

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
        $separator = $separator !== '' ? $separator : ':';

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
        $ownerClass = $ownerRegistration->ownerClass;

        return match ($ownerRegistration->ownerType) {
            PermissionEntityType::Resource => static::withOwnerConfigurationContext(
                $ownerRegistration,
                static fn (): string => $ownerClass::getModelLabel(),
            ),
            PermissionEntityType::RelationManager => Str::headline(Str::beforeLast(class_basename($ownerClass), 'RelationManager')),
            PermissionEntityType::Page => Str::headline(Str::beforeLast(class_basename($ownerClass), 'Page')),
            PermissionEntityType::Widget => Str::headline(Str::beforeLast(class_basename($ownerClass), 'Widget')),
            default => Str::headline(class_basename($ownerClass)),
        };
    }

    protected static function resolveAbilityLabel(string $ability): string
    {
        $translationKey = "filament-acl::filament-acl.permission_labels.{$ability}";
        $translated = __($translationKey);

        if ($translated !== $translationKey) {
            return $translated;
        }

        $snakeKey = 'filament-acl::filament-acl.permission_labels.' . Str::snake($ability);
        $snakeTranslated = __($snakeKey);

        if ($snakeTranslated !== $snakeKey) {
            return $snakeTranslated;
        }

        return Str::headline($ability);
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
     * @param  array<string, mixed>  $node
     */
    protected static function countNodePermissions(array $node): int
    {
        $count = count($node['options']);

        foreach ($node['relation_managers'] as $rm) {
            $count += count($rm['options']);
        }

        foreach ($node['children'] as $child) {
            $count += static::countNodePermissions($child);
        }

        return $count;
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
