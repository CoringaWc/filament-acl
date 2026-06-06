<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use BackedEnum;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\Permission\Contracts\Role as RoleContract;
use Throwable;
use WeakMap;

class Utils
{
    /**
     * Per-request memoization of the protected-role panel check.
     *
     * Keyed by the authenticated user instance so entries are released as soon
     * as that instance is garbage collected at the end of the request. Using a
     * WeakMap (instead of a plain static array) prevents the cache from
     * accumulating identities across long-running Octane/Swoole workers.
     *
     * @var WeakMap<object, array<string, bool>>|null
     */
    protected static ?WeakMap $protectedRoleForPanelCache = null;

    /**
     * @return class-string<Model>
     */
    public static function getRoleModel(): string
    {
        return app(StoresPermissions::class)->getRoleModel();
    }

    /**
     * @return class-string<Model>
     */
    public static function getPermissionModel(): string
    {
        return app(StoresPermissions::class)->getPermissionModel();
    }

    public static function getAuthGuard(?string $panelId = null): string
    {
        $panel = static::getPanel(static::normalizePanelId($panelId));

        if ($panel instanceof Panel) {
            return $panel->getAuthGuard();
        }

        return (string) config('auth.defaults.guard', 'web');
    }

    /**
     * @return class-string<Model>
     */
    public static function getUserModel(?string $panelId = null): string
    {
        $guard = static::getAuthGuard(static::normalizePanelId($panelId));
        $providerName = (string) config("auth.guards.{$guard}.provider");
        $providerConfig = config("auth.providers.{$providerName}");

        if (! is_array($providerConfig)) {
            /** @var class-string<Model> $fallback */
            $fallback = config('auth.providers.users.model');

            return $fallback;
        }

        if (($providerConfig['driver'] ?? null) !== 'eloquent') {
            /** @var class-string<Model> $fallback */
            $fallback = config('auth.providers.users.model');

            return $fallback;
        }

        /** @var class-string<Model> $model */
        $model = $providerConfig['model'];

        return $model;
    }

    public static function getProtectedRoleName(): string
    {
        return (string) config('filament-acl.roles.protected.name', 'super_admin');
    }

    public static function shouldHideProtectedRole(): bool
    {
        return (bool) config('filament-acl.roles.protected.hidden', true);
    }

    public static function shouldBypassGateWithProtectedRole(): bool
    {
        return (bool) config('filament-acl.roles.protected.bypass_gate', true);
    }

    public static function getPanelColumnName(): string
    {
        return (string) config('filament-acl.database.panel_scope.column', 'panel');
    }

    public static function getDefaultPanelScopeValue(): string
    {
        return (string) config('filament-acl.database.panel_scope.default', 'global');
    }

    public static function scopesRolesByPanel(?string $panelId = null): bool
    {
        return app(FilamentPermissionManager::class)->scopesRolesByPanel($panelId);
    }

    public static function scopesPermissionsByPanel(?string $panelId = null): bool
    {
        return app(FilamentPermissionManager::class)->scopesPermissionsByPanel($panelId);
    }

    public static function isProtectedRole(Model | string $role): bool
    {
        $roleName = is_string($role)
            ? $role
            : (string) $role->getAttribute('name');

        return $roleName === static::getProtectedRoleName();
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function scopeVisibleRoles(Builder $query): Builder
    {
        if (! static::shouldHideProtectedRole()) {
            return $query;
        }

        /** @phpstan-ignore argument.type */
        return $query->where('name', '!=', static::getProtectedRoleName());
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function scopeRoleQueryToPanel(Builder $query, ?string $panelId = null): Builder
    {
        $panelId = static::normalizePanelId($panelId);
        $panelScope = app(FilamentPermissionManager::class)->getPanelScope($panelId);

        if (! $panelScope->onRoles) {
            return $query;
        }

        if (! static::panelColumnExistsOnRolesTable($panelScope->column)) {
            return $query;
        }

        return $query->where(
            $panelScope->column,
            static::resolvePanelScopeValue($panelId, $panelScope->default),
        );
    }

    /**
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function scopePermissionQueryToPanel(Builder $query, ?string $panelId = null): Builder
    {
        $panelId = static::normalizePanelId($panelId);
        $panelScope = app(FilamentPermissionManager::class)->getPanelScope($panelId);

        if (! $panelScope->onPermissions) {
            return $query;
        }

        if (! static::panelColumnExistsOnPermissionsTable($panelScope->column)) {
            return $query;
        }

        return $query->where(
            $panelScope->column,
            static::resolvePanelScopeValue($panelId, $panelScope->default),
        );
    }

    /**
     * @return array<int, int|string>
     */
    public static function getHiddenRoleIds(?string $panelId = null): array
    {
        $panelId = static::normalizePanelId($panelId);
        $roleModel = static::getRoleModel();
        $query = static::scopeRoleQueryToPanel(
            $roleModel::query()
                ->where('name', static::getProtectedRoleName()), // @phpstan-ignore argument.type
            $panelId,
        );

        /** @var array<int, int|string> $hiddenRoleIds */
        $hiddenRoleIds = $query->get()
            ->map(static fn (Model $role): int | string => $role->getKey())
            ->all();

        return $hiddenRoleIds;
    }

    /**
     * @param  array<int, int|string>  $roleIds
     * @return array<int, int|string>
     */
    public static function mergeHiddenRoleIds(Model $record, array $roleIds, ?string $panelId = null): array
    {
        $panelId = static::normalizePanelId($panelId);

        if (! method_exists($record, 'roles')) {
            return array_values(array_unique($roleIds));
        }

        $hiddenRoleIds = static::getHiddenRoleIds($panelId);

        if ($hiddenRoleIds === []) {
            return array_values(array_unique($roleIds));
        }

        /** @var array<int, int|string> $assignedHiddenRoleIds */
        $assignedHiddenRoleIds = $record->roles()
            ->whereKey($hiddenRoleIds)
            ->get()
            ->map(static fn (Model $role): int | string => $role->getKey())
            ->all();

        return array_values(array_unique([
            ...$roleIds,
            ...$assignedHiddenRoleIds,
        ]));
    }

    /**
     * @return array<int, int|string>
     */
    public static function getAllPermissionIds(?string $panelId = null): array
    {
        $panelId = static::normalizePanelId($panelId);
        $permissionModel = static::getPermissionModel();
        $query = static::scopePermissionQueryToPanel($permissionModel::query(), $panelId);

        /** @var array<int, int|string> $permissionIds */
        $permissionIds = $query->get()
            ->map(static fn (Model $permission): int | string => $permission->getKey())
            ->all();

        return $permissionIds;
    }

    /**
     * @return array<string, string>
     */
    public static function panelScopeAttributes(string $table, ?string $panelId = null): array
    {
        $panelId = static::normalizePanelId($panelId);
        $panelScope = app(FilamentPermissionManager::class)->getPanelScope($panelId);

        if (($table === 'roles') && (! $panelScope->onRoles)) {
            return [];
        }

        if (($table === 'permissions') && (! $panelScope->onPermissions)) {
            return [];
        }

        if (($table === 'roles') && (! static::panelColumnExistsOnRolesTable($panelScope->column))) {
            return [];
        }

        if (($table === 'permissions') && (! static::panelColumnExistsOnPermissionsTable($panelScope->column))) {
            return [];
        }

        return [
            $panelScope->column => static::resolvePanelScopeValue($panelId, $panelScope->default),
        ];
    }

    /**
     * @return Model&RoleContract
     */
    public static function createProtectedRole(?string $panelId = null): Model
    {
        $panelId = static::normalizePanelId($panelId);
        $roleModel = static::getRoleModel();
        $attributes = [
            'name' => static::getProtectedRoleName(),
            'guard_name' => static::getAuthGuard($panelId),
            ...static::panelScopeAttributes('roles', $panelId),
        ];

        /** @var Model&RoleContract $role */
        $role = $roleModel::query()->firstOrCreate($attributes);

        return $role;
    }

    public static function userHasProtectedRoleForPanel(mixed $user, ?string $panelId = null): bool
    {
        $panelId = static::normalizePanelId($panelId);

        if (! is_object($user) || ! method_exists($user, 'roles')) {
            return false;
        }

        // The protected-role check runs inside a Gate::before() hook, so it is
        // evaluated for every authorization call (e.g. once per table row and
        // action). The result only depends on the user instance and the panel,
        // never on the authorized record, so memoize it per request to avoid an
        // N+1 of identical role-existence and schema-introspection queries.
        $cache = static::$protectedRoleForPanelCache ??= new WeakMap;
        $cacheKey = $panelId ?? '';

        $entries = $cache->offsetExists($user) ? $cache[$user] : [];

        if (array_key_exists($cacheKey, $entries)) {
            return $entries[$cacheKey];
        }

        $result = static::resolveUserHasProtectedRoleForPanel($user, $panelId);

        $entries[$cacheKey] = $result;
        $cache[$user] = $entries;

        return $result;
    }

    /**
     * Resolve the protected-role panel check against the database without any
     * memoization. Callers should go through userHasProtectedRoleForPanel().
     */
    protected static function resolveUserHasProtectedRoleForPanel(mixed $user, ?string $panelId): bool
    {
        try {
            $roleRelation = $user->roles();
        } catch (Throwable) {
            return false;
        }

        if (! $roleRelation instanceof Relation) {
            return false;
        }

        try {
            $query = $roleRelation->getQuery()
                ->where('name', static::getProtectedRoleName()); // @phpstan-ignore argument.type

            $panelScope = app(FilamentPermissionManager::class)->getPanelScope($panelId);

            if ($panelScope->onRoles && static::panelColumnExistsOnRolesTable($panelScope->column)) {
                $query->where($panelScope->column, static::resolvePanelScopeValue($panelId, $panelScope->default));
            }

            return $query->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Clear the per-request protected-role memoization cache.
     *
     * Primarily useful in tests, or after assigning/removing the protected role
     * to the currently authenticated user within the same request.
     */
    public static function flushProtectedRoleForPanelCache(): void
    {
        static::$protectedRoleForPanelCache = null;
    }

    public static function getPublishedConfigPath(): string
    {
        return config_path('filament-acl.php');
    }

    public static function hasPublishedConfig(): bool
    {
        return File::exists(static::getPublishedConfigPath());
    }

    public static function findPublishedPermissionMigration(): ?string
    {
        $matches = File::glob(database_path('migrations/*create_permission_tables.php'));

        if ($matches === []) {
            return null;
        }

        return Arr::first($matches);
    }

    public static function hasPublishedPermissionConfig(): bool
    {
        return File::exists(config_path('permission.php'));
    }

    public static function detectMorphKeyType(?string $userModelClass = null): string
    {
        $userModelClass ??= static::getUserModel();
        /** @var Model $userModel */
        $userModel = new $userModelClass;

        if (in_array(HasUlids::class, class_uses_recursive($userModelClass), true)) {
            return 'ulid';
        }

        if (in_array(HasUuids::class, class_uses_recursive($userModelClass), true)) {
            return 'uuid';
        }

        if ($userModel->getKeyType() === 'int') {
            return 'unsignedBigInteger';
        }

        return $userModel->getKeyType() === 'string' ? 'string' : 'unsignedBigInteger';
    }

    public static function getMorphKeyColumnLength(): int
    {
        return (int) config('filament-acl.database.model_morph_key.length', 36);
    }

    public static function panelColumnExistsOnRolesTable(string $column): bool
    {
        return static::panelColumnExistsOnTable(static::getRoleModel(), $column);
    }

    public static function panelColumnExistsOnPermissionsTable(string $column): bool
    {
        return static::panelColumnExistsOnTable(static::getPermissionModel(), $column);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function panelColumnExistsOnTable(string $modelClass, string $column): bool
    {
        try {
            /** @var Model $model */
            $model = new $modelClass;

            return in_array($column, $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable()), true);
        } catch (Throwable) {
            return false;
        }
    }

    public static function resolvePanelScopeValue(?string $panelId = null, ?string $default = null): string
    {
        $panelId = static::normalizePanelId($panelId);

        return $panelId
            ?? Filament::getCurrentPanel()?->getId()
            ?? $default
            ?? static::getDefaultPanelScopeValue();
    }

    public static function normalizePanelId(string | BackedEnum | null $panelId = null): ?string
    {
        if ($panelId instanceof BackedEnum) {
            return (string) $panelId->value;
        }

        return filled($panelId) ? $panelId : null;
    }

    /**
     * @param  class-string  $ownerClass
     * @return class-string
     */
    public static function resolvePermissionOwnerClass(string $ownerClass): string
    {
        $resolvedOwnerClass = $ownerClass;
        $visitedClasses = [];

        while (! in_array($resolvedOwnerClass, $visitedClasses, true)) {
            $visitedClasses[] = $resolvedOwnerClass;
            $sharedOwnerClass = match (true) {
                method_exists($resolvedOwnerClass, 'getSharedPermissionOwner') => $resolvedOwnerClass::getSharedPermissionOwner(),
                method_exists($resolvedOwnerClass, 'getPermissionOwnerClass') => $resolvedOwnerClass::getPermissionOwnerClass(),
                default => null,
            };

            if (! is_string($sharedOwnerClass) || blank($sharedOwnerClass) || ($sharedOwnerClass === $resolvedOwnerClass)) {
                break;
            }

            /** @var class-string $resolvedOwnerClass */
            $resolvedOwnerClass = $sharedOwnerClass;
        }

        return $resolvedOwnerClass;
    }

    /**
     * @param  class-string  $ownerClass
     */
    public static function shouldRegisterPermissionOwner(string $ownerClass): bool
    {
        if (
            (bool) config('filament-acl.integration.require_explicit_opt_in', true)
            && (! method_exists($ownerClass, 'shouldRegisterPermissions'))
        ) {
            return false;
        }

        if (! method_exists($ownerClass, 'shouldRegisterPermissions')) {
            return true;
        }

        return (bool) $ownerClass::shouldRegisterPermissions();
    }

    /**
     * @param  class-string  $ownerClass
     */
    public static function shouldRegisterPermissionsFor(string $ownerClass): bool
    {
        return static::shouldRegisterPermissionOwner($ownerClass);
    }

    /**
     * @param  class-string  $ownerClass
     */
    public static function shouldDisplayPermissionOwner(string $ownerClass): bool
    {
        if (! static::shouldRegisterPermissionOwner($ownerClass)) {
            return false;
        }

        return static::resolvePermissionOwnerClass($ownerClass) === $ownerClass;
    }

    /**
     * @return array<string, string>
     */
    public static function resolveCustomPermissions(?string $panelId = null): array
    {
        $panelId = static::normalizePanelId($panelId);
        $customPermissions = config('filament-acl.custom_permissions', []);

        if (! is_array($customPermissions)) {
            return [];
        }

        $resolvedPermissions = [];

        foreach ($customPermissions as $permissionName => $definition) {
            $resolvedPermission = match (true) {
                is_string($permissionName) => static::resolveNamedCustomPermission($permissionName, $definition, $panelId),
                is_string($definition) => static::resolveNamedCustomPermission($definition, null, $panelId),
                is_array($definition) => static::resolveArrayCustomPermission($definition, $panelId),
                default => null,
            };

            if (($resolvedPermission === null) || blank($resolvedPermission['name'])) {
                continue;
            }

            $resolvedPermissions[$resolvedPermission['name']] = $resolvedPermission['label'];
        }

        ksort($resolvedPermissions);

        return $resolvedPermissions;
    }

    /**
     * @param  class-string  $entityClass
     */
    public static function inferPermissionEntityType(string $entityClass): ?PermissionEntityType
    {
        return match (true) {
            is_subclass_of($entityClass, Resource::class) => PermissionEntityType::Resource,
            is_subclass_of($entityClass, RelationManager::class) => PermissionEntityType::RelationManager,
            is_subclass_of($entityClass, Page::class) => PermissionEntityType::Page,
            is_subclass_of($entityClass, Widget::class) => PermissionEntityType::Widget,
            default => null,
        };
    }

    public static function defaultPermissionSubject(
        string $entityClass,
        PermissionEntityType $entityType,
        ?string $registrationKey = null,
    ): string {
        $resolvedOwnerClass = class_exists($entityClass)
            ? static::resolvePermissionOwnerClass($entityClass)
            : $entityClass;
        $normalizedClass = str_replace('/', '\\', $resolvedOwnerClass);
        /** @var Collection<int, string> $segments */
        $segments = collect(explode('\\', trim($normalizedClass, '\\')))
            ->filter()
            ->values();

        $filamentIndex = $segments->search('Filament');

        if ($filamentIndex !== false) {
            $segments = $segments->slice($filamentIndex + 1)->values();
        }

        if ($entityType === PermissionEntityType::Resource) {
            $segments = $segments->reject(static fn (string $segment): bool => $segment === 'Resources')->values();
        }

        if ($entityType === PermissionEntityType::Page) {
            $segments = $segments->reject(static fn (string $segment): bool => $segment === 'Pages')->values();
        }

        if ($entityType === PermissionEntityType::Widget) {
            $segments = $segments->reject(static fn (string $segment): bool => $segment === 'Widgets')->values();
        }

        if ($entityType === PermissionEntityType::RelationManager) {
            $segments = $segments
                ->reject(static fn (string $segment): bool => in_array($segment, ['Resources', 'RelationManagers'], true))
                ->values();
        }

        $lastSegment = (string) $segments->last();
        $normalizedLastSegment = Str::of($lastSegment)
            ->replace(['Resource', 'RelationManager', 'Page', 'Widget'], '')
            ->toString();

        if (filled($normalizedLastSegment)) {
            $segments = $segments->slice(0, -1)
                ->push($normalizedLastSegment)
                ->values();
        }

        if ($segments->count() > 1) {
            $lastSegment = (string) $segments->last();
            $previousSegment = (string) $segments->slice(-2, 1)->first();

            if (
                filled($lastSegment)
                && filled($previousSegment)
                && Str::of(Str::singular($previousSegment))->endsWith(Str::singular($lastSegment))
            ) {
                $segments = $segments->slice(0, -1)->values();
            }
        }

        $subject = $segments
            ->values()
            ->map(static function (string $segment, int $index) use ($segments): string {
                if ($index < ($segments->count() - 1)) {
                    return Str::studly(Str::singular($segment));
                }

                return Str::studly($segment);
            })
            ->implode('');

        if (blank($subject)) {
            $subject = Str::studly(class_basename($resolvedOwnerClass));
        }

        if ($registrationKey === null) {
            return $subject;
        }

        return Str::of($subject)
            ->append(Str::studly($registrationKey))
            ->toString();
    }

    public static function makeMorphKeyDefinition(string $columnExpression, string $type): string
    {
        return match ($type) {
            'ulid' => "\$table->ulid({$columnExpression});",
            'uuid' => "\$table->uuid({$columnExpression});",
            'string' => "\$table->string({$columnExpression});",
            default => "\$table->unsignedBigInteger({$columnExpression});",
        };
    }

    public static function makeNullableKeyDefinition(string $columnExpression, string $type): string
    {
        return match ($type) {
            'ulid' => "\$table->ulid({$columnExpression})->nullable();",
            'uuid' => "\$table->uuid({$columnExpression})->nullable();",
            'string' => "\$table->string({$columnExpression})->nullable();",
            default => "\$table->unsignedBigInteger({$columnExpression})->nullable();",
        };
    }

    public static function shouldProhibitCommands(): bool
    {
        return app()->environment('production')
            && (bool) config('filament-acl.commands.prohibit_in_production', true);
    }

    protected static function getPanel(?string $panelId = null): ?Panel
    {
        if (! app()->bound('filament')) {
            return null;
        }

        if ($panelId !== null) {
            return Filament::getPanel($panelId);
        }

        return Filament::getCurrentPanel() ?? Filament::getDefaultPanel();
    }

    /**
     * @return array{name: string, label: string}|null
     */
    protected static function resolveNamedCustomPermission(
        string $permissionName,
        mixed $definition,
        ?string $panelId,
    ): ?array {
        if (is_string($definition) && filled($definition)) {
            return [
                'name' => $permissionName,
                'label' => $definition,
            ];
        }

        if (is_array($definition)) {
            if (static::customPermissionTargetsPanel($definition, $panelId) === false) {
                return null;
            }

            if (array_is_list($definition) && filled($panelPermissionName = $definition[0] ?? null) && is_string($panelPermissionName)) {
                return [
                    'name' => $panelPermissionName,
                    'label' => static::formatCustomPermissionLabel($panelPermissionName),
                ];
            }

            return [
                'name' => $permissionName,
                'label' => filled($definition['label'] ?? null)
                    ? (string) $definition['label']
                    : static::formatCustomPermissionLabel($permissionName),
            ];
        }

        return [
            'name' => $permissionName,
            'label' => static::formatCustomPermissionLabel($permissionName),
        ];
    }

    /**
     * @param  array<int|string, mixed>  $definition
     * @return array{name: string, label: string}|null
     */
    protected static function resolveArrayCustomPermission(array $definition, ?string $panelId): ?array
    {
        if (static::customPermissionTargetsPanel($definition, $panelId) === false) {
            return null;
        }

        if (filled($permissionName = $definition['name'] ?? null) && is_string($permissionName)) {
            return [
                'name' => $permissionName,
                'label' => filled($definition['label'] ?? null)
                    ? (string) $definition['label']
                    : static::formatCustomPermissionLabel($permissionName),
            ];
        }

        if (($panelId !== null) && is_string($panelSpecificPermission = $definition[$panelId] ?? null) && filled($panelSpecificPermission)) {
            return [
                'name' => $panelSpecificPermission,
                'label' => static::formatCustomPermissionLabel($panelSpecificPermission),
            ];
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $definition
     */
    protected static function customPermissionTargetsPanel(array $definition, ?string $panelId): bool
    {
        if ($panelId === null) {
            return true;
        }

        $panels = $definition['panels'] ?? null;

        if ($panels === null) {
            return true;
        }

        $normalizedPanels = array_values(array_filter(array_map(
            static function (mixed $panel): ?string {
                if ($panel instanceof BackedEnum) {
                    return (string) $panel->value;
                }

                return is_string($panel) && filled($panel) ? $panel : null;
            },
            Arr::wrap($panels),
        )));

        if ($normalizedPanels === []) {
            return true;
        }

        return in_array($panelId, $normalizedPanels, true);
    }

    protected static function formatCustomPermissionLabel(string $permissionName): string
    {
        $separator = (string) config('filament-acl.permissions.separator', ':');

        return Str::of($permissionName)
            ->replace($separator, ' ')
            ->headline()
            ->toString();
    }
}
