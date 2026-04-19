<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

test('it resolves the relation manager subject for view any', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $owner = User::factory()->create();
    Post::factory()->count(2)->for($owner)->create();

    $this->grantOwnerPermission($actor, 'viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager);
    $this->actingAs($actor);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $actor,
        ability: 'viewAny',
        target: Post::class,
        action: PostsRelationManager::class,
    );

    $this->assertTrue($response->allowed());
    $this->assertTrue(PostsRelationManager::canViewForRecord($owner, EditUser::class));
});
test('it denies the relation manager when the permission is missing', function () {
    /** @var TestCase $this */
    $actor = $this->createUser();
    $owner = User::factory()->create();

    $this->actingAs($actor);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $actor,
        ability: 'viewAny',
        target: Post::class,
        action: PostsRelationManager::class,
    );

    $this->assertFalse($response->allowed());
    $this->assertSame(
        'Missing permission [' . $this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager) . '].',
        $response->message(),
    );
    $this->assertFalse(PostsRelationManager::canViewForRecord($owner, EditUser::class));
});
