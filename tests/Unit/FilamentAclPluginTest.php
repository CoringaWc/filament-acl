<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\FilamentAclPlugin;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResourceConfiguration;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Panel;
use PHPUnit\Framework\MockObject\MockObject;

test('it uses config defaults for panel scope', function () {
    /** @var TestCase $this */
    $plugin = FilamentAclPlugin::make();

    $this->assertFalse($plugin->usesStrictMode());
    $this->assertFalse($plugin->scopesRolesByPanel());
    $this->assertFalse($plugin->scopesPermissionsByPanel());
});
test('it can override panel scope and register panel configuration', function () {
    /** @var TestCase $this */
    $panel = $this->mockBuilder(Panel::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getId'])
        ->getMock();
    /** @var MockObject&Panel $panel */
    $panel->expects($this->onceExpectation())
        ->method('getId')
        ->willReturn('admin');

    $plugin = FilamentAclPlugin::make()
        ->strictMode()
        ->scopeRolesByPanel()
        ->scopePermissionsByPanel();

    $plugin->register($panel);

    $manager = $this->appContainer()->make(FilamentPermissionManager::class);

    $this->assertTrue($manager->usesStrictMode('admin'));
    $this->assertTrue($manager->scopesRolesByPanel('admin'));
    $this->assertTrue($manager->scopesPermissionsByPanel('admin'));
});
test('make returns a fresh plugin instance', function () {
    /** @var TestCase $this */
    $firstPlugin = FilamentAclPlugin::make()->strictMode();
    $secondPlugin = FilamentAclPlugin::make();

    $this->assertTrue($firstPlugin->usesStrictMode());
    $this->assertFalse($secondPlugin->usesStrictMode());
    $this->assertNotSame($firstPlugin, $secondPlugin);
});
test('it can configure the permissions resource without a callback', function () {
    /** @var TestCase $this */
    $panel = $this->mockBuilder(Panel::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getId', 'resources'])
        ->getMock();
    /** @var MockObject&Panel $panel */
    $panel->expects($this->onceExpectation())
        ->method('getId')
        ->willReturn('admin');

    $panel->expects($this->onceExpectation())
        ->method('resources')
        ->with($this->callbackConstraint(function (array $resources): bool {
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
});
