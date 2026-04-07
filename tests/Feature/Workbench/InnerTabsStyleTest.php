<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Schemas\Components\Tabs;
use ReflectionMethod;

class InnerTabsStyleTest extends TestCase
{
    // ── makeInnerTabs ──────────────────────────────────────────────────────

    public function test_inner_tabs_are_horizontal_by_default(): void
    {
        config(['filament-acl.resources.permissions.inner_tabs.vertical' => false]);

        $tabs = $this->callMakeInnerTabs('test_tabs', []);

        self::assertInstanceOf(Tabs::class, $tabs);
        self::assertFalse($this->isVertical($tabs), 'Inner tabs should be horizontal by default');
    }

    public function test_inner_tabs_are_vertical_when_configured(): void
    {
        config(['filament-acl.resources.permissions.inner_tabs.vertical' => true]);

        $tabs = $this->callMakeInnerTabs('test_tabs', []);

        self::assertInstanceOf(Tabs::class, $tabs);
        self::assertTrue($this->isVertical($tabs), 'Inner tabs should be vertical when configured');
    }

    // ── sections collapsed ─────────────────────────────────────────────────

    public function test_sections_collapsed_default_is_false(): void
    {
        self::assertFalse(
            (bool) config('filament-acl.resources.permissions.sections.collapsed'),
            'Sections should be expanded (not collapsed) by default',
        );
    }

    public function test_sections_persist_collapsed_default_is_true(): void
    {
        self::assertTrue(
            (bool) config('filament-acl.resources.permissions.sections.persist_collapsed'),
            'Section collapsed state should be persisted by default',
        );
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function callMakeInnerTabs(string $name, array $tabs): Tabs
    {
        $method = new ReflectionMethod(PermissionResource::class, 'makeInnerTabs');

        return $method->invoke(null, $name, $tabs);
    }

    private function isVertical(Tabs $tabs): bool
    {
        return $tabs->isVertical();
    }
}
