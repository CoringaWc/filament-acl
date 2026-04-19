<?php

declare(strict_types=1);

use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Facades\FilamentPermission;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use CoringaWc\FilamentAcl\Support\ConfiguredPermissionSubjectResolver;
use CoringaWc\FilamentAcl\Support\DefaultPermissionKeyBuilder;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\SpatiePermissionStore;
use CoringaWc\FilamentAcl\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('it registers the manager and config', function (): void {
    /** @var TestCase $this */
    $manager = $this->appContainer()->make(FilamentPermissionManager::class);

    expect($manager)->toBeInstanceOf(FilamentPermissionManager::class);
    expect($this->appContainer()->make(ConfiguredPermissionSubjectResolver::class))->toBeInstanceOf(ConfiguredPermissionSubjectResolver::class);
    expect($this->appContainer()->make(DefaultPermissionKeyBuilder::class))->toBeInstanceOf(DefaultPermissionKeyBuilder::class);
    expect($this->appContainer()->make(SpatiePermissionStore::class))->toBeInstanceOf(SpatiePermissionStore::class);
    expect($this->appContainer()->make(ResolvesPermissionSubject::class))->toBeInstanceOf(ResolvesPermissionSubject::class);
    expect($this->appContainer()->make(BuildsPermissionKey::class))->toBeInstanceOf(BuildsPermissionKey::class);
    expect($this->appContainer()->make(StoresPermissions::class))->toBeInstanceOf(StoresPermissions::class);
    expect(config('filament-acl.database.panel_scope.on_roles'))->toBeFalse();
    expect(config('filament-acl.database.panel_scope.on_permissions'))->toBeFalse();
    expect(FilamentPermission::defaultPermissionKeyBuilder('viewAny', 'TenantUsers'))->toBe('ViewAny:TenantUsers');
});

test('consuming app can override subject resolver via container', function (): void {
    /** @var TestCase $this */
    $custom = new class implements ResolvesPermissionSubject
    {
        public function resolve(string $entityClass, PermissionEntityType $entityType, ?string $panelId = null, ?string $registrationKey = null, array $meta = []): string
        {
            return 'custom-subject';
        }
    };

    $this->appContainer()->singleton(ResolvesPermissionSubject::class, $custom::class);

    expect($this->appContainer()->make(ResolvesPermissionSubject::class))->toBeInstanceOf($custom::class);
    expect($this->appContainer()->make(ResolvesPermissionSubject::class)->resolve('Any', PermissionEntityType::Resource))->toBe('custom-subject');
});

test('consuming app can override key builder via container', function (): void {
    /** @var TestCase $this */
    $custom = new class implements BuildsPermissionKey
    {
        public function build(string $ability, PermissionAction | string $permissionAction): string
        {
            return 'custom-key';
        }
    };

    $this->appContainer()->singleton(BuildsPermissionKey::class, $custom::class);

    expect($this->appContainer()->make(BuildsPermissionKey::class))->toBeInstanceOf($custom::class);
    expect($this->appContainer()->make(BuildsPermissionKey::class)->build('viewAny', 'Posts'))->toBe('custom-key');
});

test('consuming app can override permission store via container', function (): void {
    /** @var TestCase $this */
    $custom = new class implements StoresPermissions
    {
        /** @return class-string<Model> */
        public function getPermissionModel(): string
        {
            return Permission::class;
        }

        /** @return class-string<Model> */
        public function getRoleModel(): string
        {
            return Role::class;
        }

        public function scopesRolesByPanel(?string $panelId = null): bool
        {
            return false;
        }

        public function scopesPermissionsByPanel(?string $panelId = null): bool
        {
            return false;
        }
    };

    $this->appContainer()->singleton(StoresPermissions::class, $custom::class);

    expect($this->appContainer()->make(StoresPermissions::class))->toBeInstanceOf($custom::class);
    expect($this->appContainer()->make(StoresPermissions::class)->getPermissionModel())->toBe(Permission::class);
});
