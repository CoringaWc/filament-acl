<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Commands;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Role;

class SyncPermissionsCommandTest extends TestCase
{
    public function test_it_syncs_permissions_for_panel_resources_and_the_protected_role(): void
    {
        Artisan::call('filament-acl:sync', [
            '--panel' => ['admin'],
            '--with-protected-role' => true,
            '--no-interaction' => true,
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource),
            'guard_name' => 'web',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager),
            'guard_name' => 'web',
        ]);

        $protectedRole = Role::query()->where('name', 'super_admin')->first();

        self::assertNotNull($protectedRole);
        self::assertGreaterThan(0, $protectedRole->permissions()->count());
    }
}
