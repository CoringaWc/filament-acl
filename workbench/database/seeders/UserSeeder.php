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
            'name' => 'Workbench Super Admin',
            'email' => 'admin@filament-acl.test',
            'password' => 'password',
            'email_verified_at' => Carbon::now(),
        ]);

        User::factory()->moderator()->create([
            'name' => 'Workbench Moderator',
            'email' => 'moderator@filament-acl.test',
            'password' => 'password',
            'email_verified_at' => Carbon::now(),
        ]);

        User::factory()->postsOnly()->create([
            'name' => 'Workbench Posts Only User',
            'email' => 'posts@filament-acl.test',
            'password' => 'password',
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
