<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Feature\Workbench;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Models\Post;

class PostResourcePermissionTest extends TestCase
{
    public function test_it_allows_the_main_post_resource_when_the_user_has_the_matching_permission(): void
    {
        $user = $this->createUser();

        $this->grantOwnerPermission($user, 'viewAny', PostResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Post::class,
            action: PostResource::class,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_denies_the_main_post_resource_when_only_the_moderation_permission_exists(): void
    {
        $user = $this->createUser();

        $this->grantOwnerPermission($user, 'viewAny', ModerationPostResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Post::class,
            action: PostResource::class,
        );

        self::assertFalse($response->allowed());
        self::assertSame(
            'Missing permission [' . $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource) . '].',
            $response->message(),
        );
    }

    public function test_it_allows_the_moderation_resource_with_its_own_subject_permission(): void
    {
        $user = $this->createUser();

        $this->grantOwnerPermission($user, 'viewAny', ModerationPostResource::class, PermissionEntityType::Resource);
        $this->actingAs($user);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: $user,
            ability: 'viewAny',
            target: Post::class,
            action: ModerationPostResource::class,
        );

        self::assertTrue($response->allowed());
    }
}
