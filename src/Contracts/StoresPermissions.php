<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Contracts;

use Illuminate\Database\Eloquent\Model;

interface StoresPermissions
{
    /**
     * @return class-string<Model>
     */
    public function getPermissionModel(): string;

    /**
     * @return class-string<Model>
     */
    public function getRoleModel(): string;

    public function scopesRolesByPanel(?string $panelId = null): bool;

    public function scopesPermissionsByPanel(?string $panelId = null): bool;
}
