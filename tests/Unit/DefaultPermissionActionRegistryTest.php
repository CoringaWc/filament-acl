<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Unit;

use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Tests\TestCase;

class DefaultPermissionActionRegistryTest extends TestCase
{
    public function test_it_returns_default_resource_actions_from_configuration(): void
    {
        $registry = $this->app->make(DefaultPermissionActionRegistry::class);

        self::assertSame([
            'viewAny',
            'view',
            'create',
            'update',
            'delete',
        ], $registry->forResource());
    }

    public function test_it_adds_relation_manager_specific_actions(): void
    {
        config(['filament-acl.relation_managers.actions' => [
            'viewAny', 'view', 'create', 'update', 'delete',
            'associate', 'attach', 'detach', 'detachAny',
            'dissociate', 'dissociateAny',
        ]]);

        $registry = $this->app->make(DefaultPermissionActionRegistry::class);

        self::assertContains('attach', $registry->forRelationManager());
        self::assertContains('dissociateAny', $registry->forRelationManager());
        self::assertContains('viewAny', $registry->forRelationManager());
    }
}
