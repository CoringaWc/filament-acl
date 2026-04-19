<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it returns panel scope configuration with runtime overrides', function () {
    /** @var TestCase $this */
    config()->set('filament-acl.database.panel_scope', [
        'column' => 'panel',
        'on_roles' => false,
        'on_permissions' => false,
        'type' => 'string',
        'length' => 50,
        'nullable' => false,
        'default' => 'global',
    ]);

    $manager = $this->appContainer()->make(FilamentPermissionManager::class);

    $manager->registerPanel('admin', strictMode: true, scopeRolesByPanel: true, scopePermissionsByPanel: false);

    $panelScope = $manager->getPanelScope('admin');

    $this->assertTrue($panelScope->onRoles);
    $this->assertFalse($panelScope->onPermissions);
    $this->assertSame('panel', $panelScope->column);
    $this->assertSame('global', $panelScope->default);
});
