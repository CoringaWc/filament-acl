<?php

declare(strict_types=1);

namespace Workbench\Database\Factories;

use CoringaWc\FilamentAcl\Support\Utils;
use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\Role;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'guard_name' => 'web',
        ];
    }

    public function superAdmin(): static
    {
        return $this->state([
            'name' => Utils::getProtectedRoleName(),
            'guard_name' => 'web',
        ]);
    }

    public function moderator(): static
    {
        return $this->state([
            'name' => 'moderator',
            'guard_name' => 'web',
        ]);
    }

    public function postsOnly(): static
    {
        return $this->state([
            'name' => 'posts_only',
            'guard_name' => 'web',
        ]);
    }
}
