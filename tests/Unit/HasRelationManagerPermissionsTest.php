<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostsRelationManager;
use CoringaWc\FilamentAcl\Tests\TestCase;

class HasRelationManagerPermissionsTest extends TestCase
{
    public function test_it_merges_default_and_custom_relation_manager_actions(): void
    {
        self::assertContains('viewAny', FakePostsRelationManager::getPermissionActions());
        self::assertContains('attach', FakePostsRelationManager::getPermissionActions());
        self::assertContains('publish', FakePostsRelationManager::getPermissionActions());
    }
}
