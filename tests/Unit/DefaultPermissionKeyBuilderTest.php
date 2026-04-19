<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it builds keys from strings and permission actions', function () {
    /** @var TestCase $this */
    $builder = $this->appContainer()->make(DefaultPermissionKeyBuilder::class);
    $permissionAction = PermissionAction::forResource(
        resourceClass: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
        subject: 'TenantUsers',
        permissionAction: 'viewAny',
    );

    $this->assertSame('ViewAny:TenantUsers', $builder->build('viewAny', 'TenantUsers'));
    $this->assertSame('ViewAny:TenantUsers', $builder->build('viewAny', $permissionAction));
});
