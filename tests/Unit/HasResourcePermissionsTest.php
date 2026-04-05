<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostPolicy;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakeUser;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

class HasResourcePermissionsTest extends TestCase
{
    public function test_it_uses_contextual_permissions_for_view_any(): void
    {
        Gate::policy(FakePost::class, FakePostPolicy::class);
        $this->be(new FakeUser(['ViewAny:BlogPosts']));

        self::assertTrue(FakePostResource::can('viewAny'));
    }

    public function test_it_uses_contextual_permissions_for_record_actions(): void
    {
        Gate::policy(FakePost::class, FakePostPolicy::class);
        $this->be(new FakeUser(['Update:BlogPosts']));

        self::assertTrue(FakePostResource::can('update', new FakePost));
    }

    public function test_it_merges_default_and_custom_permission_actions(): void
    {
        self::assertContains('viewAny', FakePostResource::getPermissionActions());
        self::assertContains('publish', FakePostResource::getPermissionActions());
    }
}
