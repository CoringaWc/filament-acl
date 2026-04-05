<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Commands;

use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;
use Workbench\Database\Seeders\PermissionSeeder;

class AdminUserCommandTest extends TestCase
{
    public function test_it_assigns_the_protected_role_to_an_existing_user(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();

        Artisan::call('filament-acl:admin-user', [
            '--panel' => 'admin',
            '--user' => $user->getKey(),
            '--no-interaction' => true,
        ]);

        $user->refresh();

        self::assertTrue($user->hasRole(Utils::getProtectedRoleName()));

        $role = Role::query()->where('name', Utils::getProtectedRoleName())->firstOrFail();

        self::assertGreaterThan(0, $role->permissions()->count());
    }
}
