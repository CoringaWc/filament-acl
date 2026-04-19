<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it reads models and panel scope from configuration', function () {
    /** @var TestCase $this */
    $manager = $this->appContainer()->make(FilamentPermissionManager::class);
    $manager->registerPanel(
        panelId: 'admin',
        strictMode: true,
        scopeRolesByPanel: true,
        scopePermissionsByPanel: false,
    );

    $store = $this->appContainer()->make(SpatiePermissionStore::class);

    $this->assertSame(config('filament-acl.models.permission'), $store->getPermissionModel());
    $this->assertSame(config('filament-acl.models.role'), $store->getRoleModel());
    $this->assertTrue($store->scopesRolesByPanel('admin'));
    $this->assertFalse($store->scopesPermissionsByPanel('admin'));
});
