<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Posts\Pages\EditPost;
use Workbench\App\Filament\Resources\Posts\RelationManagers\CategoriesRelationManager;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;

test('it resolves the post categories relation manager subject for view any', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $owner = Post::factory()->create();
    $categories = Category::factory()->count(2)->create();
    $owner->categories()->attach($categories->modelKeys());

    $this->grantOwnerPermission($actor, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($actor);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $actor,
        ability: 'viewAny',
        target: Category::class,
        action: CategoriesRelationManager::class,
    );

    $this->assertTrue($response->allowed());
    $this->assertTrue(CategoriesRelationManager::canViewForRecord($owner, EditPost::class));
});
test('it denies the post categories relation manager when the permission is missing', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $owner = Post::factory()->create();

    $this->actingAs($actor);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $actor,
        ability: 'viewAny',
        target: Category::class,
        action: CategoriesRelationManager::class,
    );

    $this->assertFalse($response->allowed());
    $this->assertSame(
        'Missing permission [' . $this->permissionKeyForOwner('viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource) . '].',
        $response->message(),
    );
    $this->assertFalse(CategoriesRelationManager::canViewForRecord($owner, EditPost::class));
});
