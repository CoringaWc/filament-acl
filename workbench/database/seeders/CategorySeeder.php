<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'name' => 'Announcements',
                'description' => 'Posts used to exercise the primary resource flow.',
            ],
            [
                'name' => 'Moderation',
                'description' => 'Categories used to test contextual moderation permissions.',
            ],
            [
                'name' => 'Releases',
                'description' => 'Release-oriented records for nested-resource navigation.',
            ],
        ] as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }
    }
}
