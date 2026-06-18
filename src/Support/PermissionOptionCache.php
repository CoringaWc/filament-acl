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
        $cacheKey = $this->ownerOptionsKey(
            ownerRegistration: $ownerRegistration,
            permissionModel: $permissionModel,
            permissionNames: $permissionNames,
            panelColumn: $panelColumn,
            panelScopeValue: $panelScopeValue,
        );

        if (array_key_exists($cacheKey, $this->ownerOptions)) {
            return $this->ownerOptions[$cacheKey];
        }

        return $this->ownerOptions[$cacheKey] = $callback();
    }

    /**
     * @param  array<int, PermissionOwnerRegistration>  $ownerRegistrations
     * @param  class-string<Model>  $permissionModel
     * @param  array<string, array<string, string>>  $permissionNamesByOwner
     * @param  Closure(array<int, PermissionOwnerRegistration>, array<string, array<string, string>>): array<string, array<int|string, string>>  $callback
     * @return array<string, array<int|string, string>>
     */
    public function rememberManyOwnerOptions(
        array $ownerRegistrations,
        string $permissionModel,
        array $permissionNamesByOwner,
        ?string $panelColumn,
        ?string $panelScopeValue,
        Closure $callback,
    ): array {
        $optionsByOwner = [];
        $missingOwnerRegistrations = [];
        $missingPermissionNamesByOwner = [];
        $cacheKeysByOwner = [];

        foreach ($ownerRegistrations as $ownerRegistration) {
            $ownerKey = $ownerRegistration->uniqueKey();
            $permissionNames = $permissionNamesByOwner[$ownerKey] ?? [];

            if ($permissionNames === []) {
                $optionsByOwner[$ownerKey] = [];

                continue;
            }

            $cacheKey = $this->ownerOptionsKey(
                ownerRegistration: $ownerRegistration,
                permissionModel: $permissionModel,
                permissionNames: $permissionNames,
                panelColumn: $panelColumn,
                panelScopeValue: $panelScopeValue,
            );

            if (array_key_exists($cacheKey, $this->ownerOptions)) {
                $optionsByOwner[$ownerKey] = $this->ownerOptions[$cacheKey];

                continue;
            }

            $missingOwnerRegistrations[] = $ownerRegistration;
            $missingPermissionNamesByOwner[$ownerKey] = $permissionNames;
            $cacheKeysByOwner[$ownerKey] = $cacheKey;
        }

        if ($missingOwnerRegistrations === []) {
            return $optionsByOwner;
        }

        $freshOptionsByOwner = $callback($missingOwnerRegistrations, $missingPermissionNamesByOwner);

        foreach ($missingOwnerRegistrations as $ownerRegistration) {
            $ownerKey = $ownerRegistration->uniqueKey();
            $options = $freshOptionsByOwner[$ownerKey] ?? [];
            $this->ownerOptions[$cacheKeysByOwner[$ownerKey]] = $options;
            $optionsByOwner[$ownerKey] = $options;
        }

        return $optionsByOwner;
    }

    public function flush(): void
    {
        $this->ownerOptions = [];
    }

    /**
     * @param  class-string<Model>  $permissionModel
     * @param  array<string, string>  $permissionNames
     */
    private function ownerOptionsKey(
        PermissionOwnerRegistration $ownerRegistration,
        string $permissionModel,
        array $permissionNames,
        ?string $panelColumn,
        ?string $panelScopeValue,
    ): string {
        return implode('|', [
            $ownerRegistration->uniqueKey(),
            $permissionModel,
            $panelColumn ?? '',
            $panelScopeValue ?? '',
            hash('xxh128', serialize($permissionNames)),
        ]);
    }
}
