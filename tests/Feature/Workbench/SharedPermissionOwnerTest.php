<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource as NestedCategoryPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;
use Workbench\App\Models\Post;

test('nested post category resource does not appear in permissions ui', function () {
    /** @var TestCase $this */
    $this->assertFalse(Utils::shouldDisplayPermissionOwner(NestedPostCategoryResource::class));
});
test('nested category post resource does not appear in permissions ui', function () {
    /** @var TestCase $this */
    $this->assertFalse(Utils::shouldDisplayPermissionOwner(NestedCategoryPostResource::class));
});
test('canonical category resource still appears in permissions ui', function () {
    /** @var TestCase $this */
    $this->assertTrue(Utils::shouldDisplayPermissionOwner(CategoryResource::class));
});
test('canonical post resource still appears in permissions ui', function () {
    /** @var TestCase $this */
    $this->assertTrue(Utils::shouldDisplayPermissionOwner(PostResource::class));
});
test('nested post category resolves to canonical category resource', function () {
    /** @var TestCase $this */
    $this->assertSame(
        CategoryResource::class,
        Utils::resolvePermissionOwnerClass(NestedPostCategoryResource::class),
    );
});
test('nested category post resolves to canonical post resource', function () {
    /** @var TestCase $this */
    $this->assertSame(
        PostResource::class,
        Utils::resolvePermissionOwnerClass(NestedCategoryPostResource::class),
    );
});
test('canonical resources resolve to themselves', function () {
    /** @var TestCase $this */
    $this->assertSame(CategoryResource::class, Utils::resolvePermissionOwnerClass(CategoryResource::class));
    $this->assertSame(PostResource::class, Utils::resolvePermissionOwnerClass(PostResource::class));
});
test('nested post category resource is authorized using canonical category permission', function () {
    /** @var TestCase $this */
    $user = $this->createUser();
    $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Category::class,
        action: NestedPostCategoryResource::class,
    );

    $this->assertTrue($response->allowed());
});
test('nested category post resource is authorized using canonical post permission', function () {
    /** @var TestCase $this */
    $user = $this->createUser();
    $this->grantOwnerPermission($user, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Post::class,
        action: NestedCategoryPostResource::class,
    );

    $this->assertTrue($response->allowed());
});
test('nested post category resource is denied without any permission', function () {
    /** @var TestCase $this */
    $user = $this->createUser();
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Category::class,
        action: NestedPostCategoryResource::class,
    );

    $this->assertFalse($response->allowed());
});
test('nested category post resource is denied without any permission', function () {
    /** @var TestCase $this */
    $user = $this->createUser();
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Post::class,
        action: NestedCategoryPostResource::class,
    );

    $this->assertFalse($response->allowed());
});
test('canonical category resource is authorized independently', function () {
    /** @var TestCase $this */
    $user = $this->createUser();
    $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Category::class,
        action: CategoryResource::class,
    );

    $this->assertTrue($response->allowed());
});
