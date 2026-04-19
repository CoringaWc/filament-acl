<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;

class CategoryResourcePermissionTest extends TestCase
{
    public function test_it_allows_the_main_category_resource_when_the_user_has_the_matching_permission(): void
    {
        $user = $this->createUser();

        $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: CategoryResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_allows_the_nested_post_categories_resource_using_canonical_category_permission(): void
    {
        $user = $this->createUser();

        // Nested resource shares permissions with canonical CategoryResource.
        // Granting the canonical permission should authorize the nested resource access.
        $this->grantOwnerPermission($user, 'viewAny', CategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_denies_the_nested_post_categories_resource_when_no_canonical_permission_exists(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertFalse($response->allowed());
    }
}
