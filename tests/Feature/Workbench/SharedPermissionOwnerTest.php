<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

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

class SharedPermissionOwnerTest extends TestCase
{
    // ── shouldDisplayPermissionOwner ────────────────────────────────────────

    public function test_nested_post_category_resource_does_not_appear_in_permissions_ui(): void
    {
        self::assertFalse(Utils::shouldDisplayPermissionOwner(NestedPostCategoryResource::class));
    }

    public function test_nested_category_post_resource_does_not_appear_in_permissions_ui(): void
    {
        self::assertFalse(Utils::shouldDisplayPermissionOwner(NestedCategoryPostResource::class));
    }

    public function test_canonical_category_resource_still_appears_in_permissions_ui(): void
    {
        self::assertTrue(Utils::shouldDisplayPermissionOwner(CategoryResource::class));
    }

    public function test_canonical_post_resource_still_appears_in_permissions_ui(): void
    {
        self::assertTrue(Utils::shouldDisplayPermissionOwner(PostResource::class));
    }

    // ── resolvePermissionOwnerClass ─────────────────────────────────────────

    public function test_nested_post_category_resolves_to_canonical_category_resource(): void
    {
        self::assertSame(
            CategoryResource::class,
            Utils::resolvePermissionOwnerClass(NestedPostCategoryResource::class),
        );
    }

    public function test_nested_category_post_resolves_to_canonical_post_resource(): void
    {
        self::assertSame(
            PostResource::class,
            Utils::resolvePermissionOwnerClass(NestedCategoryPostResource::class),
        );
    }

    public function test_canonical_resources_resolve_to_themselves(): void
    {
        self::assertSame(CategoryResource::class, Utils::resolvePermissionOwnerClass(CategoryResource::class));
        self::assertSame(PostResource::class, Utils::resolvePermissionOwnerClass(PostResource::class));
    }

    // ── autorização via resource canônico ───────────────────────────────────

    public function test_nested_post_category_resource_is_authorized_using_canonical_category_permission(): void
    {
        $user = $this->createUser();
        $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_nested_category_post_resource_is_authorized_using_canonical_post_permission(): void
    {
        $user = $this->createUser();
        $this->grantOwnerPermission($user, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Post::class,
            action: NestedCategoryPostResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_nested_post_category_resource_is_denied_without_any_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertFalse($response->allowed());
    }

    public function test_nested_category_post_resource_is_denied_without_any_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Post::class,
            action: NestedCategoryPostResource::class,
        );

        self::assertFalse($response->allowed());
    }

    // ── canonical resource permanece independente ───────────────────────────

    public function test_canonical_category_resource_is_authorized_independently(): void
    {
        $user = $this->createUser();
        $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: CategoryResource::class,
        );

        self::assertTrue($response->allowed());
    }
}
