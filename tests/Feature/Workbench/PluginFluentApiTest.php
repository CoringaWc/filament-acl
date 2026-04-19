<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\FilamentAclPlugin;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use ReflectionMethod;
use Workbench\App\Filament\Resources\Posts\PostResource;

class PluginFluentApiTest extends TestCase
{
    // ── groupByNavigationGroup fluent ──────────────────────────────────────

    public function test_fluent_group_by_navigation_group_overrides_config(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $plugin = FilamentAclPlugin::get();
        $plugin->groupByNavigationGroup(false);

        self::assertFalse($plugin->usesGroupByNavigationGroup());
    }

    public function test_fluent_group_by_navigation_group_affects_section_label(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $plugin = FilamentAclPlugin::get();
        $plugin->groupByNavigationGroup(false);

        $label = $this->callResolveResourceSectionLabel(PostResource::class);

        self::assertSame(
            (string) PostResource::getNavigationLabel(),
            $label,
            'Fluent groupByNavigationGroup(false) should make Discovery return nav label, not group label',
        );
    }

    // ── groupByCluster fluent ──────────────────────────────────────────────

    public function test_fluent_group_by_cluster_overrides_config(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_cluster' => true]);

        $plugin = FilamentAclPlugin::get();
        $plugin->groupByCluster(false);

        self::assertFalse($plugin->usesGroupByCluster());
    }

    // ── innerTabsVertical fluent ───────────────────────────────────────────

    public function test_fluent_inner_tabs_vertical_overrides_config(): void
    {
        config(['filament-acl.resources.permissions.inner_tabs.vertical' => false]);

        $plugin = FilamentAclPlugin::get();
        $plugin->innerTabsVertical(true);

        self::assertTrue($plugin->usesInnerTabsVertical());
    }

    public function test_fluent_inner_tabs_vertical_affects_tabs_component(): void
    {
        config(['filament-acl.resources.permissions.inner_tabs.vertical' => false]);

        $plugin = FilamentAclPlugin::get();
        $plugin->innerTabsVertical(true);

        $tabs = $this->callMakeInnerTabs('test_tabs', []);

        self::assertTrue($tabs->isVertical(), 'Fluent innerTabsVertical(true) should make Tabs vertical');
    }

    // ── innerTabsContained fluent ──────────────────────────────────────────

    public function test_fluent_inner_tabs_contained_overrides_config(): void
    {
        config(['filament-acl.resources.permissions.inner_tabs.contained' => false]);

        $plugin = FilamentAclPlugin::get();
        $plugin->innerTabsContained(true);

        self::assertTrue($plugin->usesInnerTabsContained());
    }

    public function test_fluent_inner_tabs_contained_affects_tabs_component(): void
    {
        config(['filament-acl.resources.permissions.inner_tabs.contained' => true]);

        $plugin = FilamentAclPlugin::get();
        $plugin->innerTabsContained(false);

        $tabs = $this->callMakeInnerTabs('test_tabs', []);

        self::assertFalse($tabs->isContained(), 'Fluent innerTabsContained(false) should make Tabs not contained');
    }

    // ── sectionsCollapsed fluent ───────────────────────────────────────────

    public function test_fluent_sections_collapsed_overrides_config(): void
    {
        config(['filament-acl.resources.permissions.sections.collapsed' => false]);

        $plugin = FilamentAclPlugin::get();
        $plugin->sectionsCollapsed(true);

        self::assertTrue($plugin->usesSectionsCollapsed());
    }

    // ── sectionsPersistCollapsed fluent ─────────────────────────────────────

    public function test_fluent_sections_persist_collapsed_overrides_config(): void
    {
        config(['filament-acl.resources.permissions.sections.persist_collapsed' => true]);

        $plugin = FilamentAclPlugin::get();
        $plugin->sectionsPersistCollapsed(false);

        self::assertFalse($plugin->usesSectionsPersistCollapsed());
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function callResolveResourceSectionLabel(string $resourceClass): string
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
    private function callMakeInnerTabs(string $name, array $tabs): Tabs
    {
        $method = new ReflectionMethod(PermissionResource::class, 'makeInnerTabs');

        return $method->invoke(null, $name, $tabs);
    }
}
