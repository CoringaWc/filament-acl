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
                'name' => __('workbench::workbench.seeds.categories.announcements.name'),
                'description' => __('workbench::workbench.seeds.categories.announcements.description'),
            ],
            [
                'name' => __('workbench::workbench.seeds.categories.moderation.name'),
                'description' => __('workbench::workbench.seeds.categories.moderation.description'),
            ],
            [
                'name' => __('workbench::workbench.seeds.categories.releases.name'),
                'description' => __('workbench::workbench.seeds.categories.releases.description'),
            ],
        ] as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                $category,
            );
        }
    }
}
