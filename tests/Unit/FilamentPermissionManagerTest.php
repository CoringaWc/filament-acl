<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it builds default permission keys from config', function () {
    /** @var TestCase $this */
    $manager = $this->appContainer()->make(FilamentPermissionManager::class);

    $this->assertSame('ViewAny:TenantUsers', $manager->defaultPermissionKeyBuilder('viewAny', 'TenantUsers'));
});
test('it stores custom callbacks', function () {
    /** @var TestCase $this */
    $manager = $this->appContainer()->make(FilamentPermissionManager::class);
    $subjectResolver = static fn (): ?string => null;
    $permissionKeyBuilder = static fn (): ?string => null;

    $manager
        ->resolvePermissionSubjectUsing($subjectResolver)
        ->buildPermissionKeyUsing($permissionKeyBuilder);

    $this->assertInstanceOf(Closure::class, $manager->getPermissionSubjectResolver());
    $this->assertInstanceOf(Closure::class, $manager->getPermissionKeyBuilder());
});
test('it registers panel configuration', function () {
    /** @var TestCase $this */
    $manager = $this->appContainer()->make(FilamentPermissionManager::class);

    $manager->registerPanel(
        panelId: 'admin',
        strictMode: true,
        scopeRolesByPanel: true,
        scopePermissionsByPanel: false,
    );

    $this->assertTrue($manager->usesStrictMode('admin'));
    $this->assertTrue($manager->scopesRolesByPanel('admin'));
    $this->assertFalse($manager->scopesPermissionsByPanel('admin'));
    $this->assertSame([
        'strict_mode' => true,
        'scope_roles_by_panel' => true,
        'scope_permissions_by_panel' => false,
    ], $manager->getPanelConfiguration('admin'));
});
test('it builds permission keys from permission actions', function () {
    /** @var TestCase $this */
    $manager = $this->appContainer()->make(FilamentPermissionManager::class);
    $permissionAction = PermissionAction::forResource(
        resourceClass: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
        subject: 'Users',
        permissionAction: 'viewAny',
    );

    $this->assertSame('ViewAny:Users', $manager->buildPermissionKey('viewAny', $permissionAction));
});
