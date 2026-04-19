<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;
use Workbench\Database\Seeders\PermissionSeeder;

test('it assigns the protected role to an existing user', function () {
    /** @var TestCase $this */
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();

    Artisan::call('filament-acl:admin-user', [
        '--panel' => 'admin',
        '--user' => $user->getKey(),
        '--no-interaction' => true,
    ]);

    $user->refresh();

    $this->assertTrue($user->hasRole(Utils::getProtectedRoleName()));

    $role = Role::query()->where('name', Utils::getProtectedRoleName())->firstOrFail();

    $this->assertGreaterThan(0, $role->permissions()->count());
});
