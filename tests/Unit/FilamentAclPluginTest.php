<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\FilamentAclPlugin;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResourceConfiguration;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Panel;

class FilamentAclPluginTest extends TestCase
{
    public function test_it_uses_config_defaults_for_panel_scope(): void
    {
        $plugin = FilamentAclPlugin::make();

        self::assertFalse($plugin->usesStrictMode());
        self::assertFalse($plugin->scopesRolesByPanel());
        self::assertFalse($plugin->scopesPermissionsByPanel());
    }

    public function test_it_can_override_panel_scope_and_register_panel_configuration(): void
    {
        $panel = $this->getMockBuilder(Panel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->getMock();

        $panel->expects(self::once())
            ->method('getId')
            ->willReturn('admin');

        $plugin = FilamentAclPlugin::make()
            ->strictMode()
            ->scopeRolesByPanel()
            ->scopePermissionsByPanel();

        $plugin->register($panel);

        $manager = $this->appContainer()->make(FilamentPermissionManager::class);

        self::assertTrue($manager->usesStrictMode('admin'));
        self::assertTrue($manager->scopesRolesByPanel('admin'));
        self::assertTrue($manager->scopesPermissionsByPanel('admin'));
    }

    public function test_make_returns_a_fresh_plugin_instance(): void
    {
        $firstPlugin = FilamentAclPlugin::make()->strictMode();
        $secondPlugin = FilamentAclPlugin::make();

        self::assertTrue($firstPlugin->usesStrictMode());
        self::assertFalse($secondPlugin->usesStrictMode());
        self::assertNotSame($firstPlugin, $secondPlugin);
    }

    public function test_it_can_configure_the_permissions_resource_without_a_callback(): void
    {
        $panel = $this->getMockBuilder(Panel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'resources'])
            ->getMock();

        $panel->expects(self::once())
            ->method('getId')
            ->willReturn('admin');

        $panel->expects(self::once())
            ->method('resources')
            ->with(self::callback(function (array $resources): bool {
                if (count($resources) !== 1) {
                    return false;
                }

                $resource = $resources[0];

                return $resource instanceof PermissionResourceConfiguration
                    && $resource->getNavigationLabel() === 'Panel Permissions'
                    && $resource->getNavigationGroup() === 'Access Control'
                    && $resource->getManagedPanel() === 'app';
            }))
            ->willReturnSelf();

        FilamentAclPlugin::make()
            ->permissionsResource()
            ->permissionsResourceNavigationLabel('Panel Permissions')
            ->permissionsResourceNavigationGroup('Access Control')
            ->permissionsResourceManagedPanel('app')
            ->register($panel);
    }
}
