<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Categories\CategoryResource;
use Workbench\App\Filament\Resources\Posts\Resources\Categories\CategoryResource as NestedPostCategoryResource;
use Workbench\App\Models\Category;

test('it allows the main category resource when the user has the matching permission', function () {
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
test('it allows the nested post categories resource using canonical category permission', function () {
    /** @var TestCase $this */
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

    $this->assertTrue($response->allowed());
});
test('it denies the nested post categories resource when no canonical permission exists', function () {
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
