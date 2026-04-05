<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\FilamentPermissionManager;

class DefaultPermissionKeyBuilder implements BuildsPermissionKey
{
    public function __construct(protected FilamentPermissionManager $manager) {}

    public function build(string $ability, PermissionAction | string $permissionAction): string
    {
        return $this->manager->buildPermissionKey($ability, $permissionAction);
    }
}
