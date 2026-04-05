<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Facades;

use CoringaWc\FilamentAcl\FilamentPermissionManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \CoringaWc\FilamentAcl\FilamentPermissionManager resolvePermissionSubjectUsing(\Closure $callback)
 * @method static \CoringaWc\FilamentAcl\FilamentPermissionManager buildPermissionKeyUsing(\Closure $callback)
 * @method static bool usesStrictMode(?string $panelId = null)
 * @method static bool scopesRolesByPanel(?string $panelId = null)
 * @method static bool scopesPermissionsByPanel(?string $panelId = null)
 * @method static string defaultPermissionKeyBuilder(string $ability, string $subject, ?string $separator = null, ?string $abilityCase = null, ?string $subjectCase = null)
 *
 * @see FilamentPermissionManager
 */
class FilamentPermission extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'filament-acl.permission-manager';
    }
}
