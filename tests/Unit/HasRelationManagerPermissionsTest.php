<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManager;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManagerWithPermissionActionsAttribute;
use CoringaWc\FilamentAcl\Tests\TestCase;

class HasRelationManagerPermissionsTest extends TestCase
{
    public function test_it_merges_default_and_custom_relation_manager_actions(): void
    {
        config(['filament-acl.relation_managers.actions' => [
            'viewAny', 'view', 'create', 'update', 'delete',
            'associate', 'attach', 'detach', 'detachAny',
            'dissociate', 'dissociateAny',
        ]]);

        self::assertContains('viewAny', FakePostsRelationManager::getPermissionActions());
        self::assertContains('attach', FakePostsRelationManager::getPermissionActions());
        self::assertContains('publish', FakePostsRelationManager::getPermissionActions());
    }

    public function test_it_uses_permission_actions_attribute_to_replace_relation_manager_actions(): void
    {
        self::assertSame(['view'], FakePostsRelationManagerWithPermissionActionsAttribute::getPermissionActions());
    }
}
