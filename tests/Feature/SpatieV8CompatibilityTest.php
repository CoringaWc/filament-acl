<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use CoringaWc\FilamentAcl\Models\Permission;
use CoringaWc\FilamentAcl\Models\Role;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Exceptions\GuardDoesNotMatch;
use Spatie\Permission\Exceptions\PermissionAlreadyExists;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\PermissionRegistrar;

enum V8PermissionName: string
{
    case Read = 'v8.read';
    case Write = 'v8.write';
}

enum V8RoleName: string
{
    case Reader = 'v8.reader';
    case Writer = 'v8.writer';
}

beforeEach(function (): void {
    config()->set('permission.models', ['permission' => Permission::class, 'role' => Role::class]);
    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionClass(Permission::class)->setRoleClass(Role::class);
    $registrar->forgetCachedPermissions();
});

test('package models inherit enum and string lookups with guard isolation', function (): void {
    foreach ([Permission::class => V8PermissionName::Read, Role::class => V8RoleName::Reader] as $model => $name) {
        $web = $model::findOrCreate($name, 'web');
        $api = $model::findOrCreate($name->value, 'api');

        expect($web)->toBeInstanceOf($model)
            ->and($model::findByName($name, 'web')->getKey())->toBe($web->getKey())
            ->and($model::findByName($name->value, 'api')->getKey())->toBe($api->getKey())
            ->and($model::findOrCreate($name->value, 'web')->getKey())->toBe($web->getKey())
            ->and($web->getKey())->not->toBe($api->getKey());
    }
});

test('package models accept enum names on create and retain duplicate detection', function (): void {
    $permission = Permission::create(['name' => V8PermissionName::Read, 'guard_name' => 'web']);
    $role = Role::create(['name' => V8RoleName::Reader, 'guard_name' => 'web']);

    expect($permission->name)->toBe(V8PermissionName::Read->value)
        ->and($role->name)->toBe(V8RoleName::Reader->value);

    expect(fn () => Permission::create(['name' => V8PermissionName::Read, 'guard_name' => 'web']))
        ->toThrow(PermissionAlreadyExists::class);
    expect(fn () => Role::create(['name' => V8RoleName::Reader, 'guard_name' => 'web']))
        ->toThrow(RoleAlreadyExists::class);
});

foreach ([false, true] as $eventsEnabled) {
    test('sync methods remain fluent replace assignments and invalidate role permission cache events=' . (int) $eventsEnabled, function () use ($eventsEnabled): void {
        /** @var TestCase $this */
        config()->set('permission.events_enabled', $eventsEnabled);
        Event::fake([PermissionAttachedEvent::class, PermissionDetachedEvent::class, RoleAttachedEvent::class, RoleDetachedEvent::class]);
        Permission::findOrCreate(V8PermissionName::Read, 'web');
        Permission::findOrCreate(V8PermissionName::Write, 'web');
        $reader = Role::findOrCreate(V8RoleName::Reader, 'web');
        $writer = Role::findOrCreate(V8RoleName::Writer, 'web');
        assert($reader instanceof Role && $writer instanceof Role);
        $user = $this->createUser();
        $reader->givePermissionTo(V8PermissionName::Read);
        $writer->givePermissionTo(V8PermissionName::Write);
        $user->assignRole(V8RoleName::Reader);
        expect($user->hasPermissionTo(V8PermissionName::Read))->toBeTrue();
        app(PermissionRegistrar::class)->getPermissions();
        expect(cache()->store('array')->has('spatie.permission.cache'))->toBeTrue();

        // Discard arrangement events so assertions cover only synchronization.
        Event::fake([PermissionAttachedEvent::class, PermissionDetachedEvent::class, RoleAttachedEvent::class, RoleDetachedEvent::class]);

        expect($reader->syncPermissions(V8PermissionName::Write))->toBe($reader)
            ->and(cache()->store('array')->has('spatie.permission.cache'))->toBeFalse()
            ->and($user->fresh()?->hasPermissionTo(V8PermissionName::Read))->toBeFalse()
            ->and($user->fresh()?->hasPermissionTo(V8PermissionName::Write))->toBeTrue()
            ->and($user->syncRoles(V8RoleName::Writer))->toBe($user)
            ->and($user->hasRole(V8RoleName::Reader))->toBeFalse()
            ->and($user->hasRole(V8RoleName::Writer))->toBeTrue();

        if ($eventsEnabled) {
            if (version_compare(InstalledVersions::getVersion('spatie/laravel-permission') ?? '0', '8.2.0', '>=')) {
                Event::assertDispatched(PermissionDetachedEvent::class);
            } else {
                Event::assertNotDispatched(PermissionDetachedEvent::class);
            }
            Event::assertDispatched(PermissionAttachedEvent::class);
            Event::assertDispatched(RoleDetachedEvent::class);
            Event::assertDispatched(RoleAttachedEvent::class);
        } else {
            Event::assertNotDispatched(PermissionDetachedEvent::class);
            Event::assertNotDispatched(PermissionAttachedEvent::class);
            Event::assertNotDispatched(RoleDetachedEvent::class);
            Event::assertNotDispatched(RoleAttachedEvent::class);
        }

        $reader->syncPermissions([]);
        $user->syncRoles([]);
        expect($reader->permissions()->count())->toBe(0)
            ->and($user->roles()->count())->toBe(0);
    });
}

test('sync validates guards before detaching existing permissions', function (): void {
    $web = Permission::findOrCreate(V8PermissionName::Read, 'web');
    $api = Permission::findOrCreate(V8PermissionName::Write, 'api');
    $role = Role::findOrCreate(V8RoleName::Reader, 'web');
    assert($role instanceof Role);
    $role->givePermissionTo($web);

    expect(fn () => $role->syncPermissions($api))->toThrow(GuardDoesNotMatch::class);
    expect($role->fresh()?->hasPermissionTo($web))->toBeTrue();
});

test('registrar reinitialization follows the installed upstream cache lifecycle', function (): void {
    Permission::findOrCreate(V8PermissionName::Read, 'web');
    $registrar = app(PermissionRegistrar::class);
    expect($registrar->getPermissions()->pluck('name')->all())->toContain(V8PermissionName::Read->value);
    Permission::query()->where('name', V8PermissionName::Read->value)->update(['name' => 'v8.changed']);
    config()->set('permission.cache.key', 'spatie.permission.cache.v8.changed');
    $registrar->initializeCache();

    if (version_compare(InstalledVersions::getVersion('spatie/laravel-permission') ?? '0', '8.3.0', '>=')) {
        expect($registrar->getPermissions()->pluck('name')->all())->toContain('v8.changed');
        $this->assertNotContains(V8PermissionName::Read->value, $registrar->getPermissions()->pluck('name')->all());
    } else {
        expect($registrar->getPermissions()->pluck('name')->all())->toContain(V8PermissionName::Read->value);
        $registrar->forgetCachedPermissions();
        expect($registrar->getPermissions()->pluck('name')->all())->toContain('v8.changed');
    }
});
