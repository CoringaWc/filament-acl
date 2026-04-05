<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Posts\Pages\EditPost;
use Workbench\App\Filament\Resources\Posts\RelationManagers\CategoriesRelationManager;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class CategoriesRelationManagerPermissionTest extends TestCase
{
    public function test_it_resolves_the_post_categories_relation_manager_subject_for_view_any(): void
    {
        $actor = User::factory()->create();
        $owner = Post::factory()->create();
        $categories = Category::factory()->count(2)->create();
        $owner->categories()->attach($categories->modelKeys());

        $this->grantOwnerPermission($actor, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($actor);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $actor,
            ability: 'viewAny',
            target: Category::class,
            action: CategoriesRelationManager::class,
        );

        self::assertTrue($response->allowed());
        self::assertTrue(CategoriesRelationManager::canViewForRecord($owner, EditPost::class));
    }

    public function test_it_denies_the_post_categories_relation_manager_when_the_permission_is_missing(): void
    {
        $actor = User::factory()->create();
        $owner = Post::factory()->create();

        $this->actingAs($actor);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $actor,
            ability: 'viewAny',
            target: Category::class,
            action: CategoriesRelationManager::class,
        );

        self::assertFalse($response->allowed());
        self::assertSame(
            'Missing permission [' . $this->permissionKeyForOwner('viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource) . '].',
            $response->message(),
        );
        self::assertFalse(CategoriesRelationManager::canViewForRecord($owner, EditPost::class));
    }
}
