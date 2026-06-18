<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl;

use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Policies\RolePolicy;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\PermissionActionResolver;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Support\PermissionOptionCache;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use CoringaWc\FilamentAcl\Support\Utils;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentPermissionServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-acl';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile('filament-acl')
            ->hasTranslations()
            ->hasMigration('create_permission_tables')
            ->hasCommands([
                Commands\AdminUserCommand::class,
                Commands\InstallCommand::class,
                Commands\SyncPermissionsCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(FilamentPermissionManager::class);
        $this->app->singleton(ResolvesPermissionSubject::class, ConfiguredPermissionSubjectResolver::class);
        $this->app->singleton(BuildsPermissionKey::class, DefaultPermissionKeyBuilder::class);
        $this->app->singleton(StoresPermissions::class, SpatiePermissionStore::class);
        $this->app->singleton(ConfiguredPermissionSubjectResolver::class);
        $this->app->singleton(DefaultPermissionActionRegistry::class);
        $this->app->singleton(DefaultPermissionKeyBuilder::class);
        $this->app->singleton(PermissionOwnerDiscovery::class);
        $this->app->singleton(PermissionActionResolver::class);
        $this->app->singleton(PermissionGate::class);
        $this->app->scoped(PermissionOptionCache::class);
        $this->app->singleton(SpatiePermissionStore::class);
        $this->app->alias(FilamentPermissionManager::class, 'filament-acl.permission-manager');
    }

    public function packageBooted(): void
    {
        if (Utils::shouldProhibitCommands()) {
            Commands\AdminUserCommand::prohibit();
            Commands\InstallCommand::prohibit();
            Commands\SyncPermissionsCommand::prohibit();
        }

        if ((bool) config('filament-acl.policies.register_role_policy', true)) {
            $roleModel = Utils::getRoleModel();
            /** @var class-string $rolePolicy */
            $rolePolicy = config('filament-acl.policies.role_policy', RolePolicy::class);

            if (Gate::getPolicyFor($roleModel) === null) {
                Gate::policy($roleModel, $rolePolicy);
            }
        }

        if (Utils::shouldBypassGateWithProtectedRole()) {
            Gate::before(static function (mixed $user): ?bool {
                return Utils::userHasProtectedRoleForPanel($user) ? true : null;
            });
        }

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/filament-acl'),
        ], 'filament-acl-stubs');
    }
}
