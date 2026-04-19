<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\FilamentAclPlugin;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Workbench\App\Filament\Resources\Posts\PostResource;

test('fluent group by navigation group overrides config', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $plugin = FilamentAclPlugin::get();
    $plugin->groupByNavigationGroup(false);

    $this->assertFalse($plugin->usesGroupByNavigationGroup());
});
test('fluent group by navigation group affects section label', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $plugin = FilamentAclPlugin::get();
    $plugin->groupByNavigationGroup(false);

    $label = callPluginFluentResolveResourceSectionLabel(PostResource::class);

    $this->assertSame(
        (string) PostResource::getNavigationLabel(),
        $label,
        'Fluent groupByNavigationGroup(false) should make Discovery return nav label, not group label',
    );
});
test('fluent group by cluster overrides config', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_cluster' => true]);

    $plugin = FilamentAclPlugin::get();
    $plugin->groupByCluster(false);

    $this->assertFalse($plugin->usesGroupByCluster());
});
test('fluent inner tabs vertical overrides config', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.vertical' => false]);

    $plugin = FilamentAclPlugin::get();
    $plugin->innerTabsVertical(true);

    $this->assertTrue($plugin->usesInnerTabsVertical());
});
test('fluent inner tabs vertical affects tabs component', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.vertical' => false]);

    $plugin = FilamentAclPlugin::get();
    $plugin->innerTabsVertical(true);

    $tabs = callPluginFluentMakeInnerTabs('test_tabs', []);

    $this->assertTrue($tabs->isVertical(), 'Fluent innerTabsVertical(true) should make Tabs vertical');
});
test('fluent inner tabs contained overrides config', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.contained' => false]);

    $plugin = FilamentAclPlugin::get();
    $plugin->innerTabsContained(true);

    $this->assertTrue($plugin->usesInnerTabsContained());
});
test('fluent inner tabs contained affects tabs component', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.inner_tabs.contained' => true]);

    $plugin = FilamentAclPlugin::get();
    $plugin->innerTabsContained(false);

    $tabs = callPluginFluentMakeInnerTabs('test_tabs', []);

    $this->assertFalse($tabs->isContained(), 'Fluent innerTabsContained(false) should make Tabs not contained');
});
test('fluent sections collapsed overrides config', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.collapsed' => false]);

    $plugin = FilamentAclPlugin::get();
    $plugin->sectionsCollapsed(true);

    $this->assertTrue($plugin->usesSectionsCollapsed());
});
test('fluent sections persist collapsed overrides config', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.persist_collapsed' => true]);

    $plugin = FilamentAclPlugin::get();
    $plugin->sectionsPersistCollapsed(false);

    $this->assertFalse($plugin->usesSectionsPersistCollapsed());
});
// ── helpers ─────────────────────────────────────────────────────────────
function callPluginFluentResolveResourceSectionLabel(string $resourceClass): string
{
    $discovery = app(PermissionOwnerDiscovery::class);
    $method = new ReflectionMethod(PermissionOwnerDiscovery::class, 'resolveResourceSectionLabel');
    $panel = Filament::getCurrentPanel();

    assert($panel instanceof Panel);

    return $method->invoke($discovery, $panel, $resourceClass);
}
/**
 * @param  array<int, Tab>  $tabs
 */
function callPluginFluentMakeInnerTabs(string $name, array $tabs): Tabs
{
    $method = new ReflectionMethod(PermissionResource::class, 'makeInnerTabs');

    return $method->invoke(null, $name, $tabs);

}
