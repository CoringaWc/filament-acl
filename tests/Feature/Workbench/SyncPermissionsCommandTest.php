<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionOwnerDiscovery;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Categories\RelationManagers\PostsRelationManager as CategoriesPostsRelationManager;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Widgets\PostsOverviewWidget;

class SyncPermissionsCommandTest extends TestCase
{
    public function test_it_syncs_permissions_for_the_admin_panel(): void
    {
        Permission::query()->delete();

        Artisan::call('filament-acl:sync', [
            '--panel' => ['admin'],
        ]);

        self::assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource),
        ]);
        self::assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('viewAny', CategoryResource::class, PermissionEntityType::Resource),
        ]);
        self::assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager),
        ]);
        self::assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('view', ContentInsightsPage::class, PermissionEntityType::Page),
        ]);
        self::assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('view', PostsOverviewWidget::class, PermissionEntityType::Widget),
        ]);
        self::assertDatabaseHas('permissions', [
            'name' => 'content.export',
        ]);
    }

    public function test_excluded_relation_managers_are_not_discovered(): void
    {
        config([
            'filament-acl.relation_managers.exclude' => [
                CategoriesPostsRelationManager::class,
            ],
        ]);

        $discovery = app(PermissionOwnerDiscovery::class);
        $panel = Filament::getCurrentPanel();

        $resourceRegistrations = $discovery->discoverResources($panel);

        $categoryRegistration = collect($resourceRegistrations)
            ->first(fn (PermissionOwnerRegistration $r): bool => $r->ownerClass === CategoryResource::class);

        self::assertNotNull($categoryRegistration, 'CategoryResource should be discovered');

        $rmRegistrations = $discovery->discoverRelationManagers($panel, $categoryRegistration);

        $excludedClasses = collect($rmRegistrations)
            ->pluck('ownerClass')
            ->all();

        self::assertNotContains(
            CategoriesPostsRelationManager::class,
            $excludedClasses,
            'Excluded relation manager should not be discovered',
        );
    }
}
