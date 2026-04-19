<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

test('inner tabs are horizontal by default', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.vertical' => false]);

    $tabs = callInnerTabsStyleMakeInnerTabs('test_tabs', []);

    $this->assertInstanceOf(Tabs::class, $tabs);
    $this->assertFalse(isInnerTabsStyleVertical($tabs), 'Inner tabs should be horizontal by default');
});
test('inner tabs are vertical when configured', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.vertical' => true]);

    $tabs = callInnerTabsStyleMakeInnerTabs('test_tabs', []);

    $this->assertInstanceOf(Tabs::class, $tabs);
    $this->assertTrue(isInnerTabsStyleVertical($tabs), 'Inner tabs should be vertical when configured');
});
test('inner tabs are not contained when config is false', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.contained' => false]);

    $tabs = callInnerTabsStyleMakeInnerTabs('test_tabs', []);

    $this->assertInstanceOf(Tabs::class, $tabs);
    $this->assertFalse($tabs->isContained(), 'Inner tabs should not be contained when configured false');
});
test('inner tabs are contained when config is true', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.contained' => true]);

    $tabs = callInnerTabsStyleMakeInnerTabs('test_tabs', []);

    $this->assertInstanceOf(Tabs::class, $tabs);
    $this->assertTrue($tabs->isContained(), 'Inner tabs should be contained when configured true');
});
test('sections collapsed default is false', function () {
    /** @var TestCase $this */
    $this->assertFalse(
        (bool) config('filament-acl.resources.permissions.sections.collapsed'),
        'Sections should be expanded (not collapsed) by default',
    );
});
test('sections persist collapsed default is true', function () {
    /** @var TestCase $this */
    $this->assertTrue(
        (bool) config('filament-acl.resources.permissions.sections.persist_collapsed'),
        'Section collapsed state should be persisted by default',
    );
});
// ── helpers ─────────────────────────────────────────────────────────────
/**
 * @param  array<int, Tab>  $tabs
 */
function callInnerTabsStyleMakeInnerTabs(string $name, array $tabs): Tabs
{
    $method = new ReflectionMethod(PermissionResource::class, 'makeInnerTabs');

    return $method->invoke(null, $name, $tabs);
}
function isInnerTabsStyleVertical(Tabs $tabs): bool
{
    return $tabs->isVertical();
}
