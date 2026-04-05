<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Tests\TestCase;

class PermissionActionTest extends TestCase
{
    public function test_it_builds_resource_actions(): void
    {
        $permissionAction = PermissionAction::forResource(
            resourceClass: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
            subject: 'Users',
            permissionAction: 'viewAny',
            panelId: 'admin',
            registrationKey: 'default',
        );

        self::assertSame(PermissionEntityType::Resource, $permissionAction->ownerType);
        self::assertSame('Users', $permissionAction->subject);
        self::assertSame('viewAny', $permissionAction->permissionAction);
        self::assertSame('admin', $permissionAction->panelId);
        self::assertSame('default', $permissionAction->registrationKey);
    }

    public function test_it_builds_relation_manager_actions(): void
    {
        $permissionAction = PermissionAction::forRelationManager(
            relationManagerClass: 'App\\Filament\\Admin\\RelationManagers\\TenantUsersRelationManager',
            subject: 'TenantUsers',
            permissionAction: 'transfer',
            panelId: 'admin',
            relatedResource: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
            registrationKey: 'default',
        );

        self::assertSame(PermissionEntityType::RelationManager, $permissionAction->ownerType);
        self::assertSame('TenantUsers', $permissionAction->subject);
        self::assertSame('transfer', $permissionAction->permissionAction);
        self::assertSame('App\\Filament\\Admin\\Resources\\Users\\UserResource', $permissionAction->relatedResource);
    }
}
