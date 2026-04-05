<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Contracts;

interface StoresPermissions
{
    /**
     * @return class-string
     */
    public function getPermissionModel(): string;

    /**
     * @return class-string
     */
    public function getRoleModel(): string;

    public function scopesRolesByPanel(?string $panelId = null): bool;

    public function scopesPermissionsByPanel(?string $panelId = null): bool;
}
