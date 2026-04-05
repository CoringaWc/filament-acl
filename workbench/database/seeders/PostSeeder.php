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
            ['title' => 'Workbench Draft Post'],
            [
                'user_id' => $viewer->getKey(),
                'status' => 'draft',
                'content' => 'A draft post seeded for the Filament ACL workbench.',
            ],
        );

        $lockedPost = Post::query()->updateOrCreate(
            ['title' => 'Workbench Locked Post'],
            [
                'user_id' => $admin->getKey(),
                'status' => 'locked',
                'content' => 'A locked post to exercise policy domain rules.',
            ],
        );

        $moderationPost = Post::query()->updateOrCreate(
            ['title' => 'Workbench Moderation Post'],
            [
                'user_id' => $moderator->getKey(),
                'status' => 'review',
                'content' => 'A post intended for the moderation resource.',
            ],
        );

        $this->seedComments($draftPost, [
            'Draft comment from the workbench seeder.',
            'Second draft comment to make relation tables less empty.',
        ]);

        $this->seedComments($lockedPost, [
            'Locked posts help exercise policy denials.',
        ]);

        $this->seedComments($moderationPost, [
            'Moderation comments make the demo dataset easier to inspect.',
        ]);

        $draftPost->categories()->sync(
            Category::query()
                ->whereIn('name', ['Announcements', 'Releases'])
                ->pluck('id')
                ->all(),
        );

        $lockedPost->categories()->sync(
            Category::query()
                ->whereIn('name', ['Releases'])
                ->pluck('id')
                ->all(),
        );

        $moderationPost->categories()->sync(
            Category::query()
                ->whereIn('name', ['Moderation', 'Announcements'])
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
