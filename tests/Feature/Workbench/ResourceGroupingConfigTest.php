<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;

test('section label returns nav group when grouping enabled', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $label = callResourceGroupingResolveSectionLabel(PostResource::class);

    $this->assertSame('Blog', $label, 'Should return nav group label when grouping is enabled');
});
test('section label returns nav label when no group', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $label = callResourceGroupingResolveSectionLabel(ModerationPostResource::class);

    $this->assertSame(
        (string) ModerationPostResource::getNavigationLabel(),
        $label,
        'Should return resource navigation label when no group exists',
    );
});
test('section label returns nav label when grouping disabled', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);

    $label = callResourceGroupingResolveSectionLabel(PostResource::class);

    $this->assertSame(
        (string) PostResource::getNavigationLabel(),
        $label,
        'Should return resource navigation label when group_by_navigation_group is false',
    );
});
test('categories and posts share label when grouping enabled', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $postsLabel = callResourceGroupingResolveSectionLabel(PostResource::class);
    $categoriesLabel = callResourceGroupingResolveSectionLabel(CategoryResource::class);

    $this->assertSame($postsLabel, $categoriesLabel, 'Posts and Categories should share Blog section label');
});
test('categories and posts have different labels when grouping disabled', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);

    $postsLabel = callResourceGroupingResolveSectionLabel(PostResource::class);
    $categoriesLabel = callResourceGroupingResolveSectionLabel(CategoryResource::class);

    $this->assertNotSame($postsLabel, $categoriesLabel, 'Posts and Categories should have different labels when grouping disabled');
});
test('resource with nav group is not standalone when grouping enabled', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $sectionLabel = callResourceGroupingResolveSectionLabel(PostResource::class);
    $navLabel = (string) PostResource::getNavigationLabel();

    $nodes = [['section_label' => $sectionLabel, 'label' => $navLabel]];

    $this->assertFalse(
        callResourceGroupingIsSectionStandaloneByLabel($nodes),
        'Resource in nav group should NOT be standalone when grouping enabled',
    );
});
test('resource with nav group is standalone when grouping disabled', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => false]);
    config(['filament-acl.resources.permissions.sections.group_by_cluster' => false]);

    $sectionLabel = callResourceGroupingResolveSectionLabel(PostResource::class);
    $navLabel = (string) PostResource::getNavigationLabel();

    $nodes = [['section_label' => $sectionLabel, 'label' => $navLabel]];

    $this->assertTrue(
        callResourceGroupingIsSectionStandaloneByLabel($nodes),
        'Resource in nav group should be standalone when grouping disabled',
    );
});
test('resource without group is always standalone', function () {
    /** @var TestCase $this */
    config(['filament-acl.resources.permissions.sections.group_by_navigation_group' => true]);

    $sectionLabel = callResourceGroupingResolveSectionLabel(ModerationPostResource::class);
    $navLabel = (string) ModerationPostResource::getNavigationLabel();

    $nodes = [['section_label' => $sectionLabel, 'label' => $navLabel]];

    $this->assertTrue(
        callResourceGroupingIsSectionStandaloneByLabel($nodes),
        'Resource without nav group should always be standalone',
    );
});
// ── helpers ─────────────────────────────────────────────────────────────
function callResourceGroupingResolveSectionLabel(string $resourceClass): string
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
function callResourceGroupingIsSectionStandaloneByLabel(array $nodes): bool
{
    $method = new ReflectionMethod(PermissionResource::class, 'isSectionStandaloneByLabel');

    return $method->invoke(null, $nodes);

}
