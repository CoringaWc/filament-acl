<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it builds resource actions', function () {
    /** @var TestCase $this */
    $permissionAction = PermissionAction::forResource(
        resourceClass: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
        subject: 'Users',
        permissionAction: 'viewAny',
        panelId: 'admin',
        registrationKey: 'default',
    );

    $this->assertSame(PermissionEntityType::Resource, $permissionAction->ownerType);
    $this->assertSame('Users', $permissionAction->subject);
    $this->assertSame('viewAny', $permissionAction->permissionAction);
    $this->assertSame('admin', $permissionAction->panelId);
    $this->assertSame('default', $permissionAction->registrationKey);
});
test('it builds relation manager actions', function () {
    /** @var TestCase $this */
    $permissionAction = PermissionAction::forRelationManager(
        relationManagerClass: 'App\\Filament\\Admin\\RelationManagers\\TenantUsersRelationManager',
        subject: 'TenantUsers',
        permissionAction: 'transfer',
        panelId: 'admin',
        relatedResource: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
        registrationKey: 'default',
    );

    $this->assertSame(PermissionEntityType::RelationManager, $permissionAction->ownerType);
    $this->assertSame('TenantUsers', $permissionAction->subject);
    $this->assertSame('transfer', $permissionAction->permissionAction);
    $this->assertSame('App\\Filament\\Admin\\Resources\\Users\\UserResource', $permissionAction->relatedResource);
});
