<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Support\PermissionOptionCache;
use CoringaWc\FilamentAcl\Support\PermissionOwnerRegistration;
use CoringaWc\FilamentAcl\Tests\Fixtures\FakePostResource;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    app(PermissionOptionCache::class)->flush();
});

afterEach(function (): void {
    app(PermissionOptionCache::class)->flush();
    DB::disableQueryLog();
});

test('it memoizes owner permission options within the request lifecycle', function (): void {
    /** @var TestCase $this */
    $registration = new PermissionOwnerRegistration(
        ownerClass: FakePostResource::class,
        ownerType: PermissionEntityType::Resource,
        panelId: 'admin',
    );

    foreach (['viewAny', 'view', 'create', 'update', 'delete', 'publish'] as $ability) {
        Permission::findOrCreate(
            $this->permissionKeyForOwner($ability, FakePostResource::class, PermissionEntityType::Resource),
            'web',
        );
    }

    $method = new ReflectionMethod(PermissionResource::class, 'getOwnerPermissionOptions');
    $method->setAccessible(true);

    DB::enableQueryLog();

    /** @var array<int|string, string> $firstOptions */
    $firstOptions = $method->invoke(null, $registration);
    $queriesAfterFirstCall = count(DB::getQueryLog());

    /** @var array<int|string, string> $secondOptions */
    $secondOptions = $method->invoke(null, $registration);
    /** @var array<int|string, string> $thirdOptions */
    $thirdOptions = $method->invoke(null, $registration);

    expect($firstOptions)->toHaveCount(6)
        ->and($secondOptions)->toBe($firstOptions)
        ->and($thirdOptions)->toBe($firstOptions)
        ->and($queriesAfterFirstCall)->toBeGreaterThan(0)
        ->and(DB::getQueryLog())->toHaveCount($queriesAfterFirstCall);
});

test('it re-evaluates owner permission options after an explicit flush', function (): void {
    /** @var TestCase $this */
    $registration = new PermissionOwnerRegistration(
        ownerClass: FakePostResource::class,
        ownerType: PermissionEntityType::Resource,
        panelId: 'admin',
    );

    Permission::findOrCreate(
        $this->permissionKeyForOwner('viewAny', FakePostResource::class, PermissionEntityType::Resource),
        'web',
    );

    $method = new ReflectionMethod(PermissionResource::class, 'getOwnerPermissionOptions');
    $method->setAccessible(true);

    /** @var array<int|string, string> $firstOptions */
    $firstOptions = $method->invoke(null, $registration);

    Permission::findOrCreate(
        $this->permissionKeyForOwner('view', FakePostResource::class, PermissionEntityType::Resource),
        'web',
    );

    /** @var array<int|string, string> $cachedOptions */
    $cachedOptions = $method->invoke(null, $registration);

    app(PermissionOptionCache::class)->flush();

    /** @var array<int|string, string> $freshOptions */
    $freshOptions = $method->invoke(null, $registration);

    expect($firstOptions)->toHaveCount(1)
        ->and($cachedOptions)->toHaveCount(1)
        ->and($freshOptions)->toHaveCount(2);
});
