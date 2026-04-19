<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Tests\TestCase;

test('it returns default resource actions from configuration', function () {
    /** @var TestCase $this */
    $registry = $this->appContainer()->make(DefaultPermissionActionRegistry::class);

    $this->assertSame([
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
    ], $registry->forResource());
});
test('it adds relation manager specific actions', function () {
    /** @var TestCase $this */
    config(['filament-acl.relation_managers.actions' => [
        'viewAny', 'view', 'create', 'update', 'delete',
        'associate', 'attach', 'detach', 'detachAny',
        'dissociate', 'dissociateAny',
    ]]);

    $registry = $this->appContainer()->make(DefaultPermissionActionRegistry::class);

    $this->assertContains('attach', $registry->forRelationManager());
    $this->assertContains('dissociateAny', $registry->forRelationManager());
    $this->assertContains('viewAny', $registry->forRelationManager());
});
