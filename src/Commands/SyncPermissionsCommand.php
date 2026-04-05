<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Commands;

use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Console\Command;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'filament-acl:sync', description: 'Synchronize Filament ACL permissions for resources and relation managers')]
class SyncPermissionsCommand extends Command
{
    use Prohibitable;

    /** @var string */
    protected $signature = 'filament-acl:sync
        {--panel=* : One or more panel IDs to sync}
        {--with-protected-role : Sync the protected role with all generated permissions}';

    public function handle(): int
    {
        if ($this->isProhibited()) {
            return self::FAILURE;
        }

        $createdPermissions = 0;

        foreach ($this->resolvePanels() as $panel) {
            Filament::setCurrentPanel($panel);

            foreach ($this->ownerDiscovery()->discoverResources($panel) as $resourceRegistration) {
                $createdPermissions += $this->syncOwnerPermissions(
                    ownerRegistration: $resourceRegistration,
                    panel: $panel,
                );

                foreach ($this->ownerDiscovery()->discoverRelationManagers($panel, $resourceRegistration) as $relationManagerRegistration) {
                    $createdPermissions += $this->syncOwnerPermissions(
                        ownerRegistration: $relationManagerRegistration,
                        panel: $panel,
                    );
                }
            }

            foreach ($this->ownerDiscovery()->discoverPages($panel) as $pageRegistration) {
                $createdPermissions += $this->syncOwnerPermissions(
                    ownerRegistration: $pageRegistration,
                    panel: $panel,
                );
            }

            foreach ($this->ownerDiscovery()->discoverWidgets($panel) as $widgetRegistration) {
                $createdPermissions += $this->syncOwnerPermissions(
                    ownerRegistration: $widgetRegistration,
                    panel: $panel,
                );
            }

            foreach (array_keys(Utils::resolveCustomPermissions($panel->getId())) as $permissionName) {
                $createdPermissions += $this->syncCustomPermission(
                    permissionName: $permissionName,
                    panel: $panel,
                );
            }

            if ((bool) $this->option('with-protected-role')) {
                $protectedRole = Utils::createProtectedRole($panel->getId());
                $protectedRole->syncPermissions(Utils::getAllPermissionIds($panel->getId()));
            }
        }

        $this->components->info(sprintf('Synchronized %d permissions.', $createdPermissions));

        return self::SUCCESS;
    }

    /**
     * @return array<int, Panel>
     */
    protected function resolvePanels(): array
    {
        /** @var array<int, string> $selectedPanels */
        $selectedPanels = array_values(array_filter(
            Arr::wrap($this->option('panel')),
            static fn (mixed $panelId): bool => is_string($panelId) && filled($panelId),
        ));

        if ($selectedPanels === []) {
            return array_values(Filament::getPanels());
        }

        return array_values(array_map(
            static fn (string $panelId): Panel => Filament::getPanel($panelId),
            $selectedPanels,
        ));
    }

    protected function syncOwnerPermissions(
        PermissionOwnerRegistration $ownerRegistration,
        Panel $panel,
    ): int {
        if (! $this->shouldSyncOwnerPermissions($ownerRegistration)) {
            return 0;
        }

        $actions = $this->resolvePermissionActions($ownerRegistration);

        if ($actions === []) {
            return 0;
        }

        $subject = $this->resolvePermissionSubject($ownerRegistration, $panel);
        $guardName = $panel->getAuthGuard();
        $permissionModel = Utils::getPermissionModel();
        $created = 0;

        foreach ($actions as $action) {
            $attributes = [
                'name' => app(FilamentPermissionManager::class)->defaultPermissionKeyBuilder($action, $subject),
                'guard_name' => $guardName,
            ];

            if (
                Utils::scopesPermissionsByPanel($panel->getId())
                && Utils::panelColumnExistsOnPermissionsTable(Utils::getPanelColumnName())
            ) {
                $attributes[Utils::getPanelColumnName()] = Utils::resolvePanelScopeValue(
                    $panel->getId(),
                    Utils::getDefaultPanelScopeValue(),
                );
            }

            /** @var Model $permission */
            $permission = $permissionModel::query()->firstOrCreate($attributes);

            if ($permission->wasRecentlyCreated) {
                $created++;
            }
        }

        return $created;
    }

    protected function syncCustomPermission(string $permissionName, Panel $panel): int
    {
        $permissionModel = Utils::getPermissionModel();
        $attributes = [
            'name' => $permissionName,
            'guard_name' => $panel->getAuthGuard(),
        ];

        if (
            Utils::scopesPermissionsByPanel($panel->getId())
            && Utils::panelColumnExistsOnPermissionsTable(Utils::getPanelColumnName())
        ) {
            $attributes[Utils::getPanelColumnName()] = Utils::resolvePanelScopeValue(
                $panel->getId(),
                Utils::getDefaultPanelScopeValue(),
            );
        }

        /** @var Model $permission */
        $permission = $permissionModel::query()->firstOrCreate($attributes);

        return $permission->wasRecentlyCreated ? 1 : 0;
    }

    protected function resolvePermissionSubject(
        PermissionOwnerRegistration $ownerRegistration,
        Panel $panel,
    ): string {
        $resolvedOwnerClass = Utils::resolvePermissionOwnerClass($ownerRegistration->ownerClass);
        $subject = method_exists($resolvedOwnerClass, 'getPermissionSubject')
            ? $resolvedOwnerClass::getPermissionSubject()
            : null;

        if (is_string($subject) && filled($subject)) {
            return $subject;
        }

        $resolved = app(ResolvesPermissionSubject::class)->resolve(
            entityClass: $ownerRegistration->ownerClass,
            entityType: $ownerRegistration->ownerType,
            panelId: $panel->getId(),
            registrationKey: $ownerRegistration->registrationKey,
            meta: array_filter([
                ...$ownerRegistration->meta,
                'related_resource' => $ownerRegistration->relatedResource,
            ], static fn (mixed $value): bool => filled($value)),
        );

        if (filled($resolved)) {
            return $resolved;
        }

        return Utils::defaultPermissionSubject(
            entityClass: $resolvedOwnerClass,
            entityType: $ownerRegistration->ownerType,
            registrationKey: $ownerRegistration->registrationKey,
        );
    }

    protected function shouldSyncOwnerPermissions(
        PermissionOwnerRegistration $ownerRegistration,
    ): bool {
        if (! Utils::shouldDisplayPermissionOwner($ownerRegistration->ownerClass)) {
            return false;
        }

        return match ($ownerRegistration->ownerType) {
            PermissionEntityType::Resource, PermissionEntityType::RelationManager, PermissionEntityType::Page, PermissionEntityType::Widget => method_exists($ownerRegistration->ownerClass, 'getPermissionActions'),
            PermissionEntityType::CustomPermission => true,
            default => false,
        };
    }

    /**
     * @return array<int, string>
     */
    protected function resolvePermissionActions(PermissionOwnerRegistration $ownerRegistration): array
    {
        if (method_exists($ownerRegistration->ownerClass, 'getPermissionActions')) {
            /** @var array<int, string> $actions */
            $actions = $ownerRegistration->registrationKey === null
                ? $ownerRegistration->ownerClass::getPermissionActions()
                : $this->withOwnerConfigurationContext(
                    $ownerRegistration,
                    callback: static fn (): array => $ownerRegistration->ownerClass::getPermissionActions(),
                );

            return $actions;
        }

        return match ($ownerRegistration->ownerType) {
            PermissionEntityType::Page => app(DefaultPermissionActionRegistry::class)->forPage(),
            PermissionEntityType::Widget => app(DefaultPermissionActionRegistry::class)->forWidget(),
            default => [],
        };
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    protected function withOwnerConfigurationContext(
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

    protected function ownerDiscovery(): PermissionOwnerDiscovery
    {
        return app(PermissionOwnerDiscovery::class);
    }
}
