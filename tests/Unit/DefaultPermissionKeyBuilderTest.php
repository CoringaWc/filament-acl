<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Tests\TestCase;

class DefaultPermissionKeyBuilderTest extends TestCase
{
    public function test_it_builds_keys_from_strings_and_permission_actions(): void
    {
        $builder = $this->app->make(DefaultPermissionKeyBuilder::class);
        $permissionAction = PermissionAction::forResource(
            resourceClass: 'App\\Filament\\Admin\\Resources\\Users\\UserResource',
            subject: 'TenantUsers',
            permissionAction: 'viewAny',
        );

        self::assertSame('ViewAny:TenantUsers', $builder->build('viewAny', 'TenantUsers'));
        self::assertSame('ViewAny:TenantUsers', $builder->build('viewAny', $permissionAction));
    }
}
