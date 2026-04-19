<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Categories\RelationManagers\PostsRelationManager as CategoriesPostsRelationManager;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Widgets\PostsOverviewWidget;

use function Pest\Laravel\assertDatabaseHas;

test('it syncs permissions for the admin panel', function () {
    /** @var TestCase $this */
    Permission::query()->delete();

    Artisan::call('filament-acl:sync', [
        '--panel' => ['admin'],
    ]);

    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource),
    ]);
    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('viewAny', CategoryResource::class, PermissionEntityType::Resource),
    ]);
    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager),
    ]);
    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('view', ContentInsightsPage::class, PermissionEntityType::Page),
    ]);
    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('view', PostsOverviewWidget::class, PermissionEntityType::Widget),
    ]);
    assertDatabaseHas('permissions', [
        'name' => 'content.export',
    ]);
});
test('excluded relation managers are not discovered', function () {
    /** @var TestCase $this */
    config([
        'filament-acl.relation_managers.exclude' => [
            CategoriesPostsRelationManager::class,
        ],
    ]);

    $discovery = app(PermissionOwnerDiscovery::class);
    $panel = Filament::getCurrentPanel();
    assert($panel instanceof Panel);

    $resourceRegistrations = $discovery->discoverResources($panel);

    $categoryRegistration = collect($resourceRegistrations)
        ->first(fn (PermissionOwnerRegistration $r): bool => $r->ownerClass === CategoryResource::class);

    $this->assertNotNull($categoryRegistration, 'CategoryResource should be discovered');

    $rmRegistrations = $discovery->discoverRelationManagers($panel, $categoryRegistration);

    $excludedClasses = collect($rmRegistrations)
        ->pluck('ownerClass')
        ->all();

    $this->assertNotContains(
        CategoriesPostsRelationManager::class,
        $excludedClasses,
        'Excluded relation manager should not be discovered',
    );
});
