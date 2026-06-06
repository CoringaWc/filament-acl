<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Support\Utils;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Workbench\App\Models\Role;
use Workbench\App\Models\User;

beforeEach(function (): void {
    Utils::flushProtectedRoleForPanelCache();
});

afterEach(function (): void {
    Utils::flushProtectedRoleForPanelCache();
});

test('it memoizes the protected role panel check per user within the request', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();

    $role = Role::firstOrCreate(
        ['name' => config('filament-acl.roles.protected.name', 'super_admin'), 'guard_name' => 'web'],
    );
    $user->assignRole($role);

    // Warm any unrelated lookups (schema, role resolution) before measuring.
    Utils::userHasProtectedRoleForPanel($user);
    Utils::flushProtectedRoleForPanelCache();

    DB::enableQueryLog();

    expect(Utils::userHasProtectedRoleForPanel($user))->toBeTrue();

    $queriesAfterFirstCall = count(DB::getQueryLog());

    // Repeated calls for the same user must not hit the database again.
    Utils::userHasProtectedRoleForPanel($user);
    Utils::userHasProtectedRoleForPanel($user);
    Utils::userHasProtectedRoleForPanel($user);

    expect(DB::getQueryLog())->toHaveCount($queriesAfterFirstCall)
        ->and($queriesAfterFirstCall)->toBeGreaterThan(0);

    DB::disableQueryLog();
});

test('it caches a negative result for users without the protected role', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();

    DB::enableQueryLog();

    expect(Utils::userHasProtectedRoleForPanel($user))->toBeFalse();

    $queriesAfterFirstCall = count(DB::getQueryLog());

    Utils::userHasProtectedRoleForPanel($user);

    expect(DB::getQueryLog())->toHaveCount($queriesAfterFirstCall);

    DB::disableQueryLog();
});

test('it isolates the memoization per user instance', function (): void {
    /** @var TestCase $this */
    $role = Role::firstOrCreate(
        ['name' => config('filament-acl.roles.protected.name', 'super_admin'), 'guard_name' => 'web'],
    );

    $privileged = $this->createUser();
    $privileged->assignRole($role);

    $regular = $this->createUser();

    expect(Utils::userHasProtectedRoleForPanel($privileged))->toBeTrue()
        ->and(Utils::userHasProtectedRoleForPanel($regular))->toBeFalse();
});

test('it re-evaluates after the cache is flushed', function (): void {
    /** @var TestCase $this */
    $user = $this->createUser();

    expect(Utils::userHasProtectedRoleForPanel($user))->toBeFalse();

    $role = Role::firstOrCreate(
        ['name' => config('filament-acl.roles.protected.name', 'super_admin'), 'guard_name' => 'web'],
    );
    $user->assignRole($role);

    // Still cached as false until explicitly flushed.
    expect(Utils::userHasProtectedRoleForPanel($user))->toBeFalse();

    Utils::flushProtectedRoleForPanelCache();

    expect(Utils::userHasProtectedRoleForPanel($user->fresh()))->toBeTrue();
});
