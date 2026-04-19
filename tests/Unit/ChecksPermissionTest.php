<?php

declare(strict_types=1);
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostPolicy;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakeUser;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Auth\Access\Response;

it('allows a policy when the user has the expected permission', function (): void {
    /** @var TestCase $this */
    $policy = new FakePostPolicy;
    $user = new FakeUser(['Update:BlogPosts']);
    $post = new FakePost;

    expect($policy->update($user, $post, FakePostResource::class)->allowed())->toBeTrue();
});
it('denies a policy when the user does not have the permission', function (): void {
    /** @var TestCase $this */
    $policy = new FakePostPolicy;
    $user = new FakeUser;
    $post = new FakePost;

    $response = $policy->update($user, $post, FakePostResource::class);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe('Missing permission [Update:BlogPosts].');
});
it('denies when no permission action and config is deny', function (): void {
    /** @var TestCase $this */
    $policy = new FakePostPolicy;
    $user = new FakeUser;
    $post = new FakePost;

    config(['filament-acl.policies.null_action_behavior' => 'deny']);

    $response = $policy->update($user, $post);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe('No permission context provided for ability [update].');
});
it('falls back when a mistyped owner class is passed', function (): void {
    /** @var TestCase $this */
    $policy = new FakePostPolicy;
    $user = new FakeUser;
    $post = new FakePost;

    config(['filament-acl.policies.null_action_behavior' => 'deny']);

    $response = $policy->update($user, $post, 'CoringaWc\\FilamentAcl\\Tests\\Fixtures\\FakePostResorce');

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe('No permission context provided for ability [update].');
});
it('keeps plain string subjects as literal permission subjects', function (): void {
    /** @var TestCase $this */
    $policy = new FakePostPolicy;
    $user = new FakeUser(['Update:BlogPosts']);
    $post = new FakePost;

    expect($policy->update($user, $post, 'BlogPosts')->allowed())->toBeTrue();
});
it('allows the domain flow when null action behavior config is allow', function (): void {
    /** @var TestCase $this */
    $policy = new FakePostPolicy;
    $user = new FakeUser;
    $post = new FakePost;

    config(['filament-acl.policies.null_action_behavior' => 'allow']);

    expect($policy->update($user, $post)->allowed())->toBeTrue();
});
it('uses custom fallback when fallback method is overridden', function (): void {
    /** @var TestCase $this */
    $policy = new class extends FakePostPolicy
    {
        public function fallbackWhenNoPermissionAction(mixed $user, string $ability): Response
        {
            return Response::deny('Custom fallback for [' . $ability . '].');
        }
    };
    $user = new FakeUser;
    $post = new FakePost;

    $response = $policy->update($user, $post);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toBe('Custom fallback for [update].');
});
