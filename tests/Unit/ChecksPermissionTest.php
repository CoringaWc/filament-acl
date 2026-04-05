<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostPolicy;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakeUser;
use CoringaWc\FilamentAcl\Tests\TestCase;

class ChecksPermissionTest extends TestCase
{
    public function test_it_allows_a_policy_when_the_user_has_the_expected_permission(): void
    {
        $policy = new FakePostPolicy;
        $user = new FakeUser(['Update:BlogPosts']);
        $post = new FakePost;

        self::assertTrue($policy->update($user, $post, FakePostResource::class)->allowed());
    }

    public function test_it_denies_a_policy_when_the_user_does_not_have_the_permission(): void
    {
        $policy = new FakePostPolicy;
        $user = new FakeUser;
        $post = new FakePost;

        $response = $policy->update($user, $post, FakePostResource::class);

        self::assertTrue($response->denied());
        self::assertSame('Missing permission [Update:BlogPosts].', $response->message());
    }

    public function test_it_allows_the_domain_flow_when_no_permission_action_is_provided(): void
    {
        $policy = new FakePostPolicy;
        $user = new FakeUser;
        $post = new FakePost;

        self::assertTrue($policy->update($user, $post)->allowed());
    }
}
