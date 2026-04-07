<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Workbench\App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->whereIn('email', [
            'admin@filament-acl.test',
            'moderator@filament-acl.test',
            'posts@filament-acl.test',
        ])->delete();

        User::factory()->superAdmin()->create([
            'name' => __('workbench::workbench.seeds.users.admin.name'),
            'email' => 'admin@filament-acl.test',
            'password' => 'password',
            'email_verified_at' => Carbon::now(),
        ]);

        User::factory()->moderator()->create([
            'name' => __('workbench::workbench.seeds.users.moderator.name'),
            'email' => 'moderator@filament-acl.test',
            'password' => 'password',
            'email_verified_at' => Carbon::now(),
        ]);

        User::factory()->postsOnly()->create([
            'name' => __('workbench::workbench.seeds.users.posts_only.name'),
            'email' => 'posts@filament-acl.test',
            'password' => 'password',
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
