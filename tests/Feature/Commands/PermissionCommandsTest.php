<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

test('sync permissions command creates permissions and syncs the protected role', function () {
    /** @var TestCase $this */
    Artisan::call('filament-acl:sync', [
        '--panel' => ['admin'],
        '--with-protected-role' => true,
    ]);

    assertDatabaseHas('permissions', [
        'name' => $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource),
        'guard_name' => 'web',
    ]);

    $protectedRole = Role::query()
        ->where('name', Utils::getProtectedRoleName())
        ->first();

    $this->assertNotNull($protectedRole);
    $this->assertSame(
        Permission::query()->count(),
        $protectedRole->permissions()->count(),
    );
});
test('admin user command creates a user and assigns the protected role', function () {
    /** @var TestCase $this */
    Artisan::call('filament-acl:admin-user', [
        '--panel' => 'admin',
        '--email' => 'cli-admin@filament-acl.test',
        '--name' => 'CLI Admin',
        '--password' => 'password',
    ]);

    $user = User::query()->where('email', 'cli-admin@filament-acl.test')->first();

    $this->assertNotNull($user);
    $this->assertTrue($user->hasRole(Utils::getProtectedRoleName()));
});
