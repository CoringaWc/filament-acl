<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
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
}
