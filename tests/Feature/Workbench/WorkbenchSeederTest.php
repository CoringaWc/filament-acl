<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Pages\ContentInsightsPage;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Filament\Widgets\PostsOverviewWidget;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;
use Workbench\Database\Seeders\DatabaseSeeder;

class WorkbenchSeederTest extends TestCase
{
    public function test_it_seeds_demo_users_roles_permissions_and_posts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@filament-acl.test')->first();
        $moderator = User::query()->where('email', 'moderator@filament-acl.test')->first();
        $postsOnlyUser = User::query()->where('email', 'posts@filament-acl.test')->first();

        self::assertNotNull($admin);
        self::assertNotNull($moderator);
        self::assertNotNull($postsOnlyUser);

        self::assertTrue($admin->hasRole(Utils::getProtectedRoleName()));
        self::assertTrue($moderator->hasRole('moderator'));
        self::assertTrue($postsOnlyUser->hasRole('posts_only'));
        self::assertTrue($admin->can($this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource)));
        self::assertTrue($admin->can($this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager)));
        self::assertTrue($moderator->can($this->permissionKeyForOwner('viewAny', ModerationPostResource::class, PermissionEntityType::Resource)));
        self::assertTrue($moderator->can($this->permissionKeyForOwner('create', PostResource::class, PermissionEntityType::Resource)));
        self::assertTrue($moderator->can($this->permissionKeyForOwner('view', ContentInsightsPage::class, PermissionEntityType::Page)));
        self::assertTrue($moderator->can($this->permissionKeyForOwner('view', PostsOverviewWidget::class, PermissionEntityType::Widget)));
        self::assertTrue($postsOnlyUser->can($this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource)));
        self::assertTrue($postsOnlyUser->can($this->permissionKeyForOwner('view', PostResource::class, PermissionEntityType::Resource)));
        self::assertFalse($postsOnlyUser->can('content.export'));
        self::assertSame(3, Post::query()->count());
    }
}
