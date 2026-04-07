<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use ReflectionMethod;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;

class ResourceGroupingConfigTest extends TestCase
{
    // ── resolveResourceSectionLabel (via Discovery) ─────────────────────────

    public function test_section_label_returns_nav_group_when_grouping_enabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $label = $this->callResolveResourceSectionLabel(PostResource::class);

        self::assertSame('Blog', $label, 'Should return nav group label when grouping is enabled');
    }

    public function test_section_label_returns_nav_label_when_no_group(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $label = $this->callResolveResourceSectionLabel(ModerationPostResource::class);

        self::assertSame(
            (string) ModerationPostResource::getNavigationLabel(),
            $label,
            'Should return resource navigation label when no group exists',
        );
    }

    public function test_section_label_returns_nav_label_when_grouping_disabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);

        $label = $this->callResolveResourceSectionLabel(PostResource::class);

        self::assertSame(
            (string) PostResource::getNavigationLabel(),
            $label,
            'Should return resource navigation label when group_by_navigation_group is false',
        );
    }

    public function test_categories_and_posts_share_label_when_grouping_enabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $postsLabel = $this->callResolveResourceSectionLabel(PostResource::class);
        $categoriesLabel = $this->callResolveResourceSectionLabel(CategoryResource::class);

        self::assertSame($postsLabel, $categoriesLabel, 'Posts and Categories should share Blog section label');
    }

    public function test_categories_and_posts_have_different_labels_when_grouping_disabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);

        $postsLabel = $this->callResolveResourceSectionLabel(PostResource::class);
        $categoriesLabel = $this->callResolveResourceSectionLabel(CategoryResource::class);

        self::assertNotSame($postsLabel, $categoriesLabel, 'Posts and Categories should have different labels when grouping disabled');
    }

    // ── isSectionStandaloneByLabel ─────────────────────────────────────────

    public function test_resource_with_nav_group_is_not_standalone_when_grouping_enabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $sectionLabel = $this->callResolveResourceSectionLabel(PostResource::class);
        $navLabel = (string) PostResource::getNavigationLabel();

        $nodes = [['section_label' => $sectionLabel, 'label' => $navLabel]];

        self::assertFalse(
            $this->callIsSectionStandaloneByLabel($nodes),
            'Resource in nav group should NOT be standalone when grouping enabled',
        );
    }

    public function test_resource_with_nav_group_is_standalone_when_grouping_disabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);
        config(['filament-acl.resources.permissions.sections.group_by_cluster' => false]);

        $sectionLabel = $this->callResolveResourceSectionLabel(PostResource::class);
        $navLabel = (string) PostResource::getNavigationLabel();

        $nodes = [['section_label' => $sectionLabel, 'label' => $navLabel]];

        self::assertTrue(
            $this->callIsSectionStandaloneByLabel($nodes),
            'Resource in nav group should be standalone when grouping disabled',
        );
    }

    public function test_resource_without_group_is_always_standalone(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $sectionLabel = $this->callResolveResourceSectionLabel(ModerationPostResource::class);
        $navLabel = (string) ModerationPostResource::getNavigationLabel();

        $nodes = [['section_label' => $sectionLabel, 'label' => $navLabel]];

        self::assertTrue(
            $this->callIsSectionStandaloneByLabel($nodes),
            'Resource without nav group should always be standalone',
        );
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
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function callIsSectionStandaloneByLabel(array $nodes): bool
    {
        $method = new ReflectionMethod(PermissionResource::class, 'isSectionStandaloneByLabel');

        return $method->invoke(null, $nodes);
    }
}
