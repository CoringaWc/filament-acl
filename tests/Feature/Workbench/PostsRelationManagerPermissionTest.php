<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\Users\Pages\EditUser;
use Workbench\App\Filament\Resources\Users\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class PostsRelationManagerPermissionTest extends TestCase
{
    public function test_it_resolves_the_relation_manager_subject_for_view_any(): void
    {
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

        self::assertTrue($response->allowed());
        self::assertTrue(PostsRelationManager::canViewForRecord($owner, EditUser::class));
    }

    public function test_it_denies_the_relation_manager_when_the_permission_is_missing(): void
    {
        $actor = $this->createUser();
        $owner = User::factory()->create();

        $this->actingAs($actor);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $actor,
            ability: 'viewAny',
            target: Post::class,
            action: PostsRelationManager::class,
        );

        self::assertFalse($response->allowed());
        self::assertSame(
            'Missing permission [' . $this->permissionKeyForOwner('viewAny', PostsRelationManager::class, PermissionEntityType::RelationManager) . '].',
            $response->message(),
        );
        self::assertFalse(PostsRelationManager::canViewForRecord($owner, EditUser::class));
    }
}
