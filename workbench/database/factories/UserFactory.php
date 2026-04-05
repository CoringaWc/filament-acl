<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use CoringaWc\FilamentAcl\Support\Utils;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => 'password',
            'remember_token' => Str::random(10),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state([
            'name' => 'Workbench Super Admin',
            'email' => 'admin@filament-acl.test',
        ])->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate([
                'name' => Utils::getProtectedRoleName(),
                'guard_name' => 'web',
            ]);

            $user->syncRoles([$role]);
        });
    }

    public function moderator(): static
    {
        return $this->state([
            'name' => 'Workbench Moderator',
            'email' => 'moderator@filament-acl.test',
        ])->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate([
                'name' => 'moderator',
                'guard_name' => 'web',
            ]);

            $user->syncRoles([$role]);
        });
    }

    public function postsOnly(): static
    {
        return $this->state([
            'name' => 'Workbench Posts Only User',
            'email' => 'posts@filament-acl.test',
        ])->afterCreating(function (User $user): void {
            $role = Role::query()->firstOrCreate([
                'name' => 'posts_only',
                'guard_name' => 'web',
            ]);

            $user->syncRoles([$role]);
        });
    }
}
