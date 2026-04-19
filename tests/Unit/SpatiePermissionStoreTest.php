<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use CoringaWc\FilamentAcl\Tests\TestCase;

class SpatiePermissionStoreTest extends TestCase
{
    public function test_it_reads_models_and_panel_scope_from_configuration(): void
    {
        $manager = $this->appContainer()->make(FilamentPermissionManager::class);
        $manager->registerPanel(
            panelId: 'admin',
            strictMode: true,
            scopeRolesByPanel: true,
            scopePermissionsByPanel: false,
        );

        $store = $this->appContainer()->make(SpatiePermissionStore::class);

        self::assertSame(config('filament-acl.models.permission'), $store->getPermissionModel());
        self::assertSame(config('filament-acl.models.role'), $store->getRoleModel());
        self::assertTrue($store->scopesRolesByPanel('admin'));
        self::assertFalse($store->scopesPermissionsByPanel('admin'));
    }
}
