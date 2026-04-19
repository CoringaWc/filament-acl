<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Tests\TestCase;

class PanelScopeTest extends TestCase
{
    public function test_it_returns_panel_scope_configuration_with_runtime_overrides(): void
    {
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

        self::assertTrue($panelScope->onRoles);
        self::assertFalse($panelScope->onPermissions);
        self::assertSame('panel', $panelScope->column);
        self::assertSame('global', $panelScope->default);
    }
}
