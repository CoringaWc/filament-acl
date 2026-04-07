<?php

declare(strict_types=1);

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\Category;
use Workbench\App\Models\Comment;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@filament-acl.test')->firstOrFail();
        $moderator = User::query()->where('email', 'moderator@filament-acl.test')->firstOrFail();
        $viewer = User::query()->where('email', 'posts@filament-acl.test')->firstOrFail();

        $draftPost = Post::query()->updateOrCreate(
            ['title' => __('workbench::workbench.seeds.posts.draft.title')],
            [
                'user_id' => $viewer->getKey(),
                'status' => 'draft',
                'content' => __('workbench::workbench.seeds.posts.draft.content'),
            ],
        );

        $lockedPost = Post::query()->updateOrCreate(
            ['title' => __('workbench::workbench.seeds.posts.locked.title')],
            [
                'user_id' => $admin->getKey(),
                'status' => 'locked',
                'content' => __('workbench::workbench.seeds.posts.locked.content'),
            ],
        );

        $moderationPost = Post::query()->updateOrCreate(
            ['title' => __('workbench::workbench.seeds.posts.moderation.title')],
            [
                'user_id' => $moderator->getKey(),
                'status' => 'review',
                'content' => __('workbench::workbench.seeds.posts.moderation.content'),
            ],
        );

        $this->seedComments($draftPost, [
            __('workbench::workbench.seeds.comments.draft_1'),
            __('workbench::workbench.seeds.comments.draft_2'),
        ]);

        $this->seedComments($lockedPost, [
            __('workbench::workbench.seeds.comments.locked_1'),
        ]);

        $this->seedComments($moderationPost, [
            __('workbench::workbench.seeds.comments.moderation_1'),
        ]);

        $draftPost->categories()->sync(
            Category::query()
                ->whereIn('name', [
                    __('workbench::workbench.seeds.categories.announcements.name'),
                    __('workbench::workbench.seeds.categories.releases.name'),
                ])
                ->pluck('id')
                ->all(),
        );

        $lockedPost->categories()->sync(
            Category::query()
                ->whereIn('name', [
                    __('workbench::workbench.seeds.categories.releases.name'),
                ])
                ->pluck('id')
                ->all(),
        );

        $moderationPost->categories()->sync(
            Category::query()
                ->whereIn('name', [
                    __('workbench::workbench.seeds.categories.moderation.name'),
                    __('workbench::workbench.seeds.categories.announcements.name'),
                ])
                ->pluck('id')
                ->all(),
        );
    }

    /**
     * @param  array<int, string>  $comments
     */
    protected function seedComments(Post $post, array $comments): void
    {
        foreach ($comments as $content) {
            Comment::query()->firstOrCreate([
                'post_id' => $post->getKey(),
                'content' => $content,
            ]);
        }
    }
}
