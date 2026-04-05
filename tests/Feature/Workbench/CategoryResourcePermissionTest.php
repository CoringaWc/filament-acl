<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;
use Workbench\App\Models\User;

class CategoryResourcePermissionTest extends TestCase
{
    public function test_it_allows_the_main_category_resource_when_the_user_has_the_matching_permission(): void
    {
        $user = User::factory()->create();

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

    public function test_it_allows_the_nested_post_categories_resource_with_its_own_subject_permission(): void
    {
        $user = User::factory()->create();

        $this->grantOwnerPermission($user, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: NestedPostCategoryResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_denies_the_main_category_resource_when_only_the_nested_permission_exists(): void
    {
        $user = User::factory()->create();

        $this->grantOwnerPermission($user, 'viewAny', NestedPostCategoryResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->app->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Category::class,
            action: CategoryResource::class,
        );

        self::assertFalse($response->allowed());
        self::assertSame(
            'Missing permission [' . $this->permissionKeyForOwner('viewAny', CategoryResource::class, PermissionEntityType::Resource) . '].',
            $response->message(),
        );
    }
}
