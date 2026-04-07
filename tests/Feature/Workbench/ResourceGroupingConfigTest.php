<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Tests\TestCase;
use ReflectionMethod;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;

class ResourceGroupingConfigTest extends TestCase
{
    // ── resolveResourceSectionLabel ─────────────────────────────────────────

    public function test_section_label_returns_nav_group_when_grouping_enabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        // Posts has NavigationGroup::Blog enum
        $label = $this->callResolveResourceSectionLabel(PostResource::class);

        self::assertSame('Blog', $label, 'Should return nav group label when grouping is enabled');
    }

    public function test_section_label_returns_nav_label_when_no_group(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        // ModerationPosts has null navigation group
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

        // Even though Posts has NavigationGroup::Blog, it should fall through
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

    // ── isSectionStandalone ────────────────────────────────────────────────

    public function test_resource_with_nav_group_is_not_standalone_when_grouping_enabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $nodes = [['owner_class' => PostResource::class]];

        self::assertFalse(
            $this->callIsSectionStandalone($nodes),
            'Resource in nav group should NOT be standalone when grouping enabled',
        );
    }

    public function test_resource_with_nav_group_is_standalone_when_grouping_disabled(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);
        config(['filament-acl.resources.permissions.sections.group_by_cluster' => false]);

        $nodes = [['owner_class' => PostResource::class]];

        self::assertTrue(
            $this->callIsSectionStandalone($nodes),
            'Resource in nav group should be standalone when grouping disabled',
        );
    }

    public function test_resource_without_group_is_always_standalone(): void
    {
        config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

        $nodes = [['owner_class' => ModerationPostResource::class]];

        self::assertTrue(
            $this->callIsSectionStandalone($nodes),
            'Resource without nav group should always be standalone',
        );
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function callResolveResourceSectionLabel(string $resourceClass): string
    {
        $method = new ReflectionMethod(PermissionResource::class, 'resolveResourceSectionLabel');

        return $method->invoke(null, $resourceClass);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private function callIsSectionStandalone(array $nodes): bool
    {
        $method = new ReflectionMethod(PermissionResource::class, 'isSectionStandalone');

        return $method->invoke(null, $nodes);
    }
}
