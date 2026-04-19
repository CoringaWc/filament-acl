<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePolicyWithoutUpdate;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostPolicy;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakeUser;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Gate;
use LogicException;

class PermissionGateTest extends TestCase
{
    public function test_it_allows_missing_policies_when_strict_mode_is_disabled(): void
    {
        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new FakeUser,
            ability: 'viewAny',
            target: FakePost::class,
            action: FakePostResource::class,
            shouldCheckPolicyExistence: true,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_fails_for_missing_policies_when_strict_mode_is_enabled(): void
    {
        config()->set('filament-acl.plugin.strict_mode', true);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Strict authorization mode is enabled, but no policy was found for [' . FakePost::class . '].');

        $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new FakeUser,
            ability: 'viewAny',
            target: FakePost::class,
            action: FakePostResource::class,
            shouldCheckPolicyExistence: true,
        );
    }

    public function test_it_allows_missing_methods_when_strict_mode_is_disabled(): void
    {
        Gate::policy(FakePost::class, FakePolicyWithoutUpdate::class);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new FakeUser,
            ability: 'update',
            target: new FakePost,
            action: FakePostResource::class,
            shouldCheckPolicyExistence: true,
        );

        self::assertTrue($response->allowed());
    }

    public function test_it_fails_for_missing_methods_when_strict_mode_is_enabled(): void
    {
        config()->set('filament-acl.plugin.strict_mode', true);
        Gate::policy(FakePost::class, FakePolicyWithoutUpdate::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Strict authorization mode is enabled, but no [update()] method was found on [' . FakePolicyWithoutUpdate::class . '].');

        $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new FakeUser,
            ability: 'update',
            target: new FakePost,
            action: FakePostResource::class,
            shouldCheckPolicyExistence: true,
        );
    }

    public function test_it_respects_gate_before_callbacks_in_the_contextual_flow(): void
    {
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

        self::assertTrue($response->denied());
        self::assertSame('Blocked by before callback.', $response->message());
    }

    public function test_it_can_inspect_existing_policy_methods_with_contextual_arguments(): void
    {
        Gate::policy(FakePost::class, FakePostPolicy::class);

        $response = $this->appContainer()->make(PermissionGate::class)->inspect(
            user: new FakeUser(['Update:BlogPosts']),
            ability: 'update',
            target: new FakePost,
            action: FakePostResource::class,
            shouldCheckPolicyExistence: true,
        );

        self::assertTrue($response->allowed());
    }
}
