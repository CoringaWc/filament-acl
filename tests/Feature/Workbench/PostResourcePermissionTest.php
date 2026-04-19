<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource as ModerationPostResource;
use Workbench\App\Filament\Resources\Posts\PostResource;
use Workbench\App\Models\Post;

test('it allows the main post resource when the user has the matching permission', function () {
    /** @var TestCase $this */
    $user = $this->createUser();

    $this->grantOwnerPermission($user, 'viewAny', PostResource::class, PermissionEntityType::Resource);
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Post::class,
        action: PostResource::class,
    );

    $this->assertTrue($response->allowed());
});
test('it denies the main post resource when only the moderation permission exists', function () {
    /** @var TestCase $this */
    $user = $this->createUser();

    $this->grantOwnerPermission($user, 'viewAny', ModerationPostResource::class, PermissionEntityType::Resource);
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Post::class,
        action: PostResource::class,
    );

    $this->assertFalse($response->allowed());
    $this->assertSame(
        'Missing permission [' . $this->permissionKeyForOwner('viewAny', PostResource::class, PermissionEntityType::Resource) . '].',
        $response->message(),
    );
});
test('it allows the moderation resource with its own subject permission', function () {
    /** @var TestCase $this */
    $user = $this->createUser();

    $this->grantOwnerPermission($user, 'viewAny', ModerationPostResource::class, PermissionEntityType::Resource);
    $this->actingAs($user);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: $user,
        ability: 'viewAny',
        target: Post::class,
        action: ModerationPostResource::class,
    );

    $this->assertTrue($response->allowed());
});
