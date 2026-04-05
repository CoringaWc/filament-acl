<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Commands;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

class PermissionCommandsTest extends TestCase
{
    public function test_sync_permissions_command_creates_permissions_and_syncs_the_protected_role(): void
    {
        Artisan::call('filament-acl:sync', [
            '--panel' => ['admin'],
            '--with-protected-role' => true,
        ]);

        self::assertDatabaseHas('permissions', [
            'name' => $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource),
            'guard_name' => 'web',
        ]);

        $protectedRole = Role::query()
            ->where('name', Utils::getProtectedRoleName())
            ->first();

        self::assertNotNull($protectedRole);
        self::assertSame(
            Permission::query()->count(),
            $protectedRole->permissions()->count(),
        );
    }

    public function test_admin_user_command_creates_a_user_and_assigns_the_protected_role(): void
    {
        Artisan::call('filament-acl:admin-user', [
            '--panel' => 'admin',
            '--email' => 'cli-admin@filament-acl.test',
            '--name' => 'CLI Admin',
            '--password' => 'password',
        ]);

        $user = User::query()->where('email', 'cli-admin@filament-acl.test')->first();

        self::assertNotNull($user);
        self::assertTrue($user->hasRole(Utils::getProtectedRoleName()));
    }
}
