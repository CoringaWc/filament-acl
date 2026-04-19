<?php

declare(strict_types=1);

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

test('it seeds demo users roles permissions and posts', function () {
    /** @var TestCase $this */
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()->where('email', 'admin@filament-acl.test')->first();
    $moderator = User::query()->where('email', 'moderator@filament-acl.test')->first();
    $postsOnlyUser = User::query()->where('email', 'posts@filament-acl.test')->first();

    $this->assertNotNull($admin);
    $this->assertNotNull($moderator);
    $this->assertNotNull($postsOnlyUser);

    $this->assertTrue($admin->hasRole(Utils::getProtectedRoleName()));
    $this->assertTrue($moderator->hasRole('moderator'));
    $this->assertTrue($postsOnlyUser->hasRole('posts_only'));
    $this->assertTrue($admin->can($this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource)));
    $this->assertTrue($admin->can($this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager)));
    $this->assertTrue($moderator->can($this->permissionKeyForOwner('viewAny', ModerationPostResource::class, PermissionEntityType::Resource)));
    $this->assertTrue($moderator->can($this->permissionKeyForOwner('create', PostResource::class, PermissionEntityType::Resource)));
    $this->assertTrue($moderator->can($this->permissionKeyForOwner('view', ContentInsightsPage::class, PermissionEntityType::Page)));
    $this->assertTrue($moderator->can($this->permissionKeyForOwner('view', PostsOverviewWidget::class, PermissionEntityType::Widget)));
    $this->assertTrue($postsOnlyUser->can($this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource)));
    $this->assertTrue($postsOnlyUser->can($this->permissionKeyForOwner('view', PostResource::class, PermissionEntityType::Resource)));
    $this->assertFalse($postsOnlyUser->can('content.export'));
    $this->assertSame(3, Post::query()->count());
});
