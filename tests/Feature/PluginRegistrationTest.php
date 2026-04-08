<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature;

use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Facades\FilamentPermission;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PluginRegistrationTest extends TestCase
{
    public function test_it_registers_the_manager_and_config(): void
    {
        $manager = $this->app->make(FilamentPermissionManager::class);

        self::assertInstanceOf(FilamentPermissionManager::class, $manager);
        self::assertInstanceOf(ConfiguredPermissionSubjectResolver::class, $this->app->make(ConfiguredPermissionSubjectResolver::class));
        self::assertInstanceOf(DefaultPermissionKeyBuilder::class, $this->app->make(DefaultPermissionKeyBuilder::class));
        self::assertInstanceOf(SpatiePermissionStore::class, $this->app->make(SpatiePermissionStore::class));
        self::assertInstanceOf(ResolvesPermissionSubject::class, $this->app->make(ResolvesPermissionSubject::class));
        self::assertInstanceOf(BuildsPermissionKey::class, $this->app->make(BuildsPermissionKey::class));
        self::assertInstanceOf(StoresPermissions::class, $this->app->make(StoresPermissions::class));
        self::assertFalse(config('filament-acl.database.panel_scope.on_roles'));
        self::assertFalse(config('filament-acl.database.panel_scope.on_permissions'));
        self::assertSame('ViewAny:TenantUsers', FilamentPermission::defaultPermissionKeyBuilder('viewAny', 'TenantUsers'));
    }

    public function test_consuming_app_can_override_subject_resolver_via_container(): void
    {
        $custom = new class implements ResolvesPermissionSubject
        {
            public function resolve(string $entityClass, PermissionEntityType $entityType, ?string $panelId = null, ?string $registrationKey = null, array $meta = []): string
            {
                return 'custom-subject';
            }
        };

        $this->app->singleton(ResolvesPermissionSubject::class, $custom::class);

        self::assertInstanceOf($custom::class, $this->app->make(ResolvesPermissionSubject::class));
        self::assertSame('custom-subject', $this->app->make(ResolvesPermissionSubject::class)->resolve('Any', PermissionEntityType::Resource));
    }

    public function test_consuming_app_can_override_key_builder_via_container(): void
    {
        $custom = new class implements BuildsPermissionKey
        {
            public function build(string $ability, PermissionAction | string $permissionAction): string
            {
                return 'custom-key';
            }
        };

        $this->app->singleton(BuildsPermissionKey::class, $custom::class);

        self::assertInstanceOf($custom::class, $this->app->make(BuildsPermissionKey::class));
        self::assertSame('custom-key', $this->app->make(BuildsPermissionKey::class)->build('viewAny', 'Posts'));
    }

    public function test_consuming_app_can_override_permission_store_via_container(): void
    {
        $custom = new class implements StoresPermissions
        {
            /** @return class-string */
            public function getPermissionModel(): string
            {
                return Permission::class;
            }

            /** @return class-string */
            public function getRoleModel(): string
            {
                return Role::class;
            }

            public function scopesRolesByPanel(?string $panelId = null): bool
            {
                return false;
            }

            public function scopesPermissionsByPanel(?string $panelId = null): bool
            {
                return false;
            }
        };

        $this->app->singleton(StoresPermissions::class, $custom::class);

        self::assertInstanceOf($custom::class, $this->app->make(StoresPermissions::class));
        self::assertSame(Permission::class, $this->app->make(StoresPermissions::class)->getPermissionModel());
    }
}
