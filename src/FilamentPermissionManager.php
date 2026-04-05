<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl;

use Closure;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PanelScope;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use Illuminate\Support\Str;

class FilamentPermissionManager
{
    protected ?Closure $resolvePermissionSubjectUsing = null;

    protected ?Closure $buildPermissionKeyUsing = null;

    /**
     * @var array<string, array{
     *     strict_mode: bool,
     *     scope_roles_by_panel: bool,
     *     scope_permissions_by_panel: bool
     * }>
     */
    protected array $panelConfigurations = [];

    public function resolvePermissionSubjectUsing(Closure $callback): static
    {
        $this->resolvePermissionSubjectUsing = $callback;

        return $this;
    }

    public function buildPermissionKeyUsing(Closure $callback): static
    {
        $this->buildPermissionKeyUsing = $callback;

        return $this;
    }

    public function getPermissionSubjectResolver(): ?Closure
    {
        return $this->resolvePermissionSubjectUsing;
    }

    public function getPermissionKeyBuilder(): ?Closure
    {
        return $this->buildPermissionKeyUsing;
    }

    public function registerPanel(
        string $panelId,
        bool $strictMode,
        bool $scopeRolesByPanel,
        bool $scopePermissionsByPanel,
    ): void {
        $this->panelConfigurations[$panelId] = [
            'strict_mode' => $strictMode,
            'scope_roles_by_panel' => $scopeRolesByPanel,
            'scope_permissions_by_panel' => $scopePermissionsByPanel,
        ];
    }

    public function usesStrictMode(?string $panelId = null): bool
    {
        if (($panelId !== null) && array_key_exists($panelId, $this->panelConfigurations)) {
            return $this->panelConfigurations[$panelId]['strict_mode'];
        }

        return (bool) config('filament-acl.plugin.strict_mode', false);
    }

    public function scopesRolesByPanel(?string $panelId = null): bool
    {
        if (($panelId !== null) && array_key_exists($panelId, $this->panelConfigurations)) {
            return $this->panelConfigurations[$panelId]['scope_roles_by_panel'];
        }

        return (bool) config('filament-acl.database.panel_scope.on_roles', false);
    }

    public function scopesPermissionsByPanel(?string $panelId = null): bool
    {
        if (($panelId !== null) && array_key_exists($panelId, $this->panelConfigurations)) {
            return $this->panelConfigurations[$panelId]['scope_permissions_by_panel'];
        }

        return (bool) config('filament-acl.database.panel_scope.on_permissions', false);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function resolvePermissionSubject(
        string $ownerClass,
        PermissionEntityType $ownerType,
        ?string $panelId = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): ?string {
        if ($this->resolvePermissionSubjectUsing instanceof Closure) {
            /** @var ?string $resolvedSubject */
            $resolvedSubject = ($this->resolvePermissionSubjectUsing)(
                $ownerClass,
                $ownerType,
                $panelId,
                $registrationKey,
                $meta,
            );

            return $resolvedSubject;
        }

        return null;
    }

    public function buildPermissionKey(string $ability, PermissionAction | string $permissionAction): string
    {
        if ($this->buildPermissionKeyUsing instanceof Closure) {
            /** @var ?string $resolvedKey */
            $resolvedKey = ($this->buildPermissionKeyUsing)($ability, $permissionAction);

            if (filled($resolvedKey)) {
                return $resolvedKey;
            }
        }

        $subject = $permissionAction instanceof PermissionAction
            ? $permissionAction->subject
            : $permissionAction;

        return $this->defaultPermissionKeyBuilder($ability, $subject);
    }

    public function defaultPermissionKeyBuilder(
        string $ability,
        string $subject,
        ?string $separator = null,
        ?string $abilityCase = null,
        ?string $subjectCase = null,
    ): string {
        $separator ??= (string) config('filament-acl.permissions.separator', ':');
        $abilityCase ??= (string) config('filament-acl.permissions.ability_case', 'studly');
        $subjectCase ??= (string) config('filament-acl.permissions.subject_case', 'preserve');

        return implode($separator, [
            $this->formatValue($ability, $abilityCase),
            $this->formatValue($subject, $subjectCase),
        ]);
    }

    public function getPanelScope(?string $panelId = null): PanelScope
    {
        /** @var array<string, mixed> $configuration */
        $configuration = config('filament-acl.database.panel_scope', []);

        return PanelScope::fromArray($configuration)->withRuntimeOverrides(
            onRoles: $this->scopesRolesByPanel($panelId),
            onPermissions: $this->scopesPermissionsByPanel($panelId),
        );
    }

    /**
     * @return array{strict_mode: bool, scope_roles_by_panel: bool, scope_permissions_by_panel: bool}|null
     */
    public function getPanelConfiguration(string $panelId): ?array
    {
        return $this->panelConfigurations[$panelId] ?? null;
    }

    protected function formatValue(string $value, string $case): string
    {
        return match ($case) {
            'camel' => Str::camel($value),
            'kebab' => Str::kebab($value),
            'lower' => Str::lower($value),
            'snake' => Str::snake($value),
            'studly' => Str::studly($value),
            'upper' => Str::upper($value),
            'preserve' => $value,
            default => $value,
        };
    }
}
