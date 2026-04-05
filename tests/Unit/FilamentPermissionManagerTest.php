<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use Closure;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Tests\TestCase;

class FilamentPermissionManagerTest extends TestCase
{
    public function test_it_builds_default_permission_keys_from_config(): void
    {
        $manager = $this->app->make(FilamentPermissionManager::class);

        self::assertSame('ViewAny:TenantUsers', $manager->defaultPermissionKeyBuilder('viewAny', 'TenantUsers'));
    }

    public function test_it_stores_custom_callbacks(): void
    {
        $manager = $this->app->make(FilamentPermissionManager::class);
        $subjectResolver = static fn (): ?string => null;
        $permissionKeyBuilder = static fn (): ?string => null;

        $manager
            ->resolvePermissionSubjectUsing($subjectResolver)
            ->buildPermissionKeyUsing($permissionKeyBuilder);

        self::assertInstanceOf(Closure::class, $manager->getPermissionSubjectResolver());
        self::assertInstanceOf(Closure::class, $manager->getPermissionKeyBuilder());
    }

    public function test_it_registers_panel_configuration(): void
    {
        $manager = $this->app->make(FilamentPermissionManager::class);

        $manager->registerPanel(
            panelId: 'admin',
            strictMode: true,
            scopeRolesByPanel: true,
            scopePermissionsByPanel: false,
        );

        self::assertTrue($manager->usesStrictMode('admin'));
        self::assertTrue($manager->scopesRolesByPanel('admin'));
        self::assertFalse($manager->scopesPermissionsByPanel('admin'));
        self::assertSame([
            'strict_mode' => true,
            'scope_roles_by_panel' => true,
            'scope_permissions_by_panel' => false,
        ], $manager->getPanelConfiguration('admin'));
    }

    public function test_it_builds_permission_keys_from_permission_actions(): void
    {
        $manager = $this->app->make(FilamentPermissionManager::class);
        $permissionAction = PermissionAction::forResource(
            resourceClass: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
            subject: 'Users',
            permissionAction: 'viewAny',
        );

        self::assertSame('ViewAny:Users', $manager->buildPermissionKey('viewAny', $permissionAction));
    }
}
