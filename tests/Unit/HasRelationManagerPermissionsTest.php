<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManager;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManagerWithPermissionActionsAttribute;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it merges default and custom relation manager actions', function () {
    /** @var TestCase $this */
    config(['filament-acl.relation_managers.actions' => [
        'viewAny', 'view', 'create', 'update', 'delete',
        'associate', 'attach', 'detach', 'detachAny',
        'dissociate', 'dissociateAny',
    ]]);

    $this->assertContains('viewAny', FakePostsRelationManager::getPermissionActions());
    $this->assertContains('attach', FakePostsRelationManager::getPermissionActions());
    $this->assertContains('publish', FakePostsRelationManager::getPermissionActions());
});
test('it uses permission actions attribute to replace relation manager actions', function () {
    /** @var TestCase $this */
    $this->assertSame(['view'], FakePostsRelationManagerWithPermissionActionsAttribute::getPermissionActions());
});
