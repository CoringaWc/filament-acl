<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Contracts;

use CoringaWc\FilamentAcl\Support\PermissionAction;

interface BuildsPermissionKey
{
    public function build(string $ability, PermissionAction | string $permissionAction): string;
}
