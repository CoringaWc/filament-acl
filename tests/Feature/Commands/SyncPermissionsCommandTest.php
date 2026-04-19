<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

test('it syncs permissions for panel resources and the protected role', function () {
    /** @var TestCase $this */
    Artisan::call('filament-acl:sync', [
        '--panel' => ['admin'],
        '--with-protected-role' => true,
        '--no-interaction' => true,
    ]);

    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource),
        'guard_name' => 'web',
    ]);

    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager),
        'guard_name' => 'web',
    ]);

    $protectedRole = Role::query()->where('name', 'super_admin')->first();

    $this->assertNotNull($protectedRole);
    $this->assertGreaterThan(0, $protectedRole->permissions()->count());
});
