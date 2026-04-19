<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePolicyWithoutUpdate;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostPolicy;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakeUser;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;

test('it allows missing policies when strict mode is disabled', function () {
    /** @var TestCase $this */
    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new FakeUser,
        ability: 'viewAny',
        target: FakePost::class,
        action: FakePostResource::class,
        shouldCheckPolicyExistence: true,
    );

    $this->assertTrue($response->allowed());
});
test('it fails for missing policies when strict mode is enabled', function () {
    /** @var TestCase $this */
    config()->set('filament-acl.plugin.strict_mode', true);

    expect(fn () => $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new FakeUser,
        ability: 'viewAny',
        target: FakePost::class,
        action: FakePostResource::class,
        shouldCheckPolicyExistence: true,
    ))->toThrow(LogicException::class, 'Strict authorization mode is enabled, but no policy was found for [' . FakePost::class . '].');
});
test('it allows missing methods when strict mode is disabled', function () {
    /** @var TestCase $this */
    Gate::policy(FakePost::class, FakePolicyWithoutUpdate::class);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new FakeUser,
        ability: 'update',
        target: new FakePost,
        action: FakePostResource::class,
        shouldCheckPolicyExistence: true,
    );

    $this->assertTrue($response->allowed());
});
test('it fails for missing methods when strict mode is enabled', function () {
    /** @var TestCase $this */
    config()->set('filament-acl.plugin.strict_mode', true);
    Gate::policy(FakePost::class, FakePolicyWithoutUpdate::class);

    expect(fn () => $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new FakeUser,
        ability: 'update',
        target: new FakePost,
        action: FakePostResource::class,
        shouldCheckPolicyExistence: true,
    ))->toThrow(LogicException::class, 'Strict authorization mode is enabled, but no [update()] method was found on [' . FakePolicyWithoutUpdate::class . '].');
});
test('it respects gate before callbacks in the contextual flow', function () {
    /** @var TestCase $this */
    Gate::before(static fn (FakeUser $user, string $ability, array $arguments): ?Response => $ability === 'viewAny'
        ? Response::deny('Blocked by before callback.')
        : null);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new FakeUser,
        ability: 'viewAny',
        target: FakePost::class,
        action: FakePostResource::class,
        shouldCheckPolicyExistence: true,
    );

    $this->assertTrue($response->denied());
    $this->assertSame('Blocked by before callback.', $response->message());
});
test('it can inspect existing policy methods with contextual arguments', function () {
    /** @var TestCase $this */
    Gate::policy(FakePost::class, FakePostPolicy::class);

    $response = $this->appContainer()->make(PermissionGate::class)->inspect(
        user: new FakeUser(['Update:BlogPosts']),
        ability: 'update',
        target: new FakePost,
        action: FakePostResource::class,
        shouldCheckPolicyExistence: true,
    );

    $this->assertTrue($response->allowed());
});
