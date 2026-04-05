<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature;

use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Facades\FilamentPermission;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use CoringaWc\FilamentAcl\Tests\TestCase;

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
}
