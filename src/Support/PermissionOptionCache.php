<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use Closure;
use Illuminate\Database\Eloquent\Model;

final class PermissionOptionCache
{
    /**
     * @var array<string, array<int|string, string>>
     */
    private array $ownerOptions = [];

    /**
     * @param  class-string<Model>  $permissionModel
     * @param  array<string, string>  $permissionNames
     * @param  Closure(): array<int|string, string>  $callback
     * @return array<int|string, string>
     */
    public function rememberOwnerOptions(
        PermissionOwnerRegistration $ownerRegistration,
        string $permissionModel,
        array $permissionNames,
        ?string $panelColumn,
        ?string $panelScopeValue,
        Closure $callback,
    ): array {
        $cacheKey = implode('|', [
            $ownerRegistration->uniqueKey(),
            $permissionModel,
            $panelColumn ?? '',
            $panelScopeValue ?? '',
            hash('xxh128', serialize($permissionNames)),
        ]);

        if (array_key_exists($cacheKey, $this->ownerOptions)) {
            return $this->ownerOptions[$cacheKey];
        }

        return $this->ownerOptions[$cacheKey] = $callback();
    }

    public function flush(): void
    {
        $this->ownerOptions = [];
    }
}
