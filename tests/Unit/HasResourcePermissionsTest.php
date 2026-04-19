<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Tests\Fixtures\FakePost;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostPolicy;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResourceWithPermissionActionsAttribute;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakeUser;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Gate;

test('it uses contextual permissions for view any', function () {
    /** @var TestCase $this */
    Gate::policy(FakePost::class, FakePostPolicy::class);
    $this->be(new FakeUser(['ViewAny:BlogPosts']));

    $this->assertTrue(FakePostResource::can('viewAny'));
});
test('it uses contextual permissions for record actions', function () {
    /** @var TestCase $this */
    Gate::policy(FakePost::class, FakePostPolicy::class);
    $this->be(new FakeUser(['Update:BlogPosts']));

    $this->assertTrue(FakePostResource::can('update', new FakePost));
});
test('it merges default and custom permission actions', function () {
    /** @var TestCase $this */
    $this->assertContains('viewAny', FakePostResource::getPermissionActions());
    $this->assertContains('publish', FakePostResource::getPermissionActions());
});
test('it uses permission actions attribute to replace the full list', function () {
    /** @var TestCase $this */
    $this->assertSame(['view'], FakePostResourceWithPermissionActionsAttribute::getPermissionActions());
});
