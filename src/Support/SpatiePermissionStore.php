<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Contracts\StoresPermissions;
use CoringaWc\FilamentAcl\FilamentPermissionManager;
use Illuminate\Database\Eloquent\Model;

class SpatiePermissionStore implements StoresPermissions
{
    public function __construct(protected FilamentPermissionManager $manager) {}

    public function getPermissionModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('filament-acl.models.permission');

        return $model;
    }

    public function getRoleModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('filament-acl.models.role');

        return $model;
    }

    public function scopesRolesByPanel(?string $panelId = null): bool
    {
        return $this->manager->scopesRolesByPanel($panelId);
    }

    public function scopesPermissionsByPanel(?string $panelId = null): bool
    {
        return $this->manager->scopesPermissionsByPanel($panelId);
    }
}
