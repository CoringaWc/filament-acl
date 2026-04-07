<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl;

use Closure;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResourceConfiguration;
use Filament\Clusters\Cluster;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Contracts\Support\Htmlable;

/** @phpstan-consistent-constructor */
class FilamentAclPlugin implements Plugin
{
    protected ?bool $isStrictMode = null;

    protected ?bool $scopeRolesByPanel = null;

    protected ?bool $scopePermissionsByPanel = null;

    protected ?bool $hasPermissionsResource = null;

    protected ?Closure $configurePermissionsResourceUsing = null;

    protected ?Closure $configurePermissionsTableUsing = null;

    /**
     * @var array{
     *     slug?: ?string,
     *     navigation_label?: ?string,
     *     navigation_icon?: string|\BackedEnum|Htmlable|null,
     *     navigation_group?: string|\UnitEnum|null,
     *     navigation_sort?: ?int,
     *     model_label?: ?string,
     *     plural_model_label?: ?string,
     *     managed_panel?: string|\BackedEnum|null,
     *     cluster?: ?string,
     *     sections?: array{
     *         group_by_navigation_group?: bool,
     *         group_by_cluster?: bool,
     *         collapsed?: bool,
     *         persist_collapsed?: bool
     *     },
     *     inner_tabs?: array{
     *         vertical?: bool
     *     }
     * }
     */
    protected array $permissionsResourceOptions = [];

    public function getId(): string
    {
        return 'filament-acl';
    }

    public function register(Panel $panel): void
    {
        app(FilamentPermissionManager::class)->registerPanel(
            panelId: $panel->getId(),
            strictMode: $this->usesStrictMode(),
            scopeRolesByPanel: $this->scopesRolesByPanel(),
            scopePermissionsByPanel: $this->scopesPermissionsByPanel(),
        );

        if ($this->hasPermissionsResource()) {
            $panel->resources([
                $this->makePermissionsResourceConfiguration(),
            ]);
        }
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        return new static;
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    public function strictMode(bool $condition = true): static
    {
        $this->isStrictMode = $condition;

        return $this;
    }

    public function usesStrictMode(): bool
    {
        return $this->isStrictMode ?? (bool) config('filament-acl.plugin.strict_mode', false);
    }

    public function isStrictMode(): bool
    {
        return $this->usesStrictMode();
    }

    public function scopeRolesByPanel(bool $condition = true): static
    {
        $this->scopeRolesByPanel = $condition;

        return $this;
    }

    public function scopesRolesByPanel(): bool
    {
        return $this->scopeRolesByPanel ?? (bool) config('filament-acl.database.panel_scope.on_roles', false);
    }

    public function scopePermissionsByPanel(bool $condition = true): static
    {
        $this->scopePermissionsByPanel = $condition;

        return $this;
    }

    public function scopesPermissionsByPanel(): bool
    {
        return $this->scopePermissionsByPanel ?? (bool) config('filament-acl.database.panel_scope.on_permissions', false);
    }

    public function permissionsResource(bool $condition = true): static
    {
        $this->hasPermissionsResource = $condition;

        return $this;
    }

    public function hasPermissionsResource(): bool
    {
        return $this->hasPermissionsResource ?? (bool) config('filament-acl.resources.permissions.enabled', false);
    }

    public function configurePermissionsResource(Closure $callback): static
    {
        $this->configurePermissionsResourceUsing = $callback;

        return $this;
    }

    public function configurePermissionsTable(Closure $callback): static
    {
        $this->configurePermissionsTableUsing = $callback;

        return $this;
    }

    public function getConfigurePermissionsTableUsing(): ?Closure
    {
        return $this->configurePermissionsTableUsing;
    }

    public function permissionsResourceSlug(?string $slug): static
    {
        $this->permissionsResourceOptions['slug'] = $slug;

        return $this;
    }

    public function permissionsResourceNavigationLabel(?string $label): static
    {
        $this->permissionsResourceOptions['navigation_label'] = $label;

        return $this;
    }

    public function permissionsResourceNavigationIcon(string | \BackedEnum | Htmlable | null $icon): static
    {
        $this->permissionsResourceOptions['navigation_icon'] = $icon;

        return $this;
    }

    public function permissionsResourceNavigationGroup(string | \UnitEnum | null $group): static
    {
        $this->permissionsResourceOptions['navigation_group'] = $group;

        return $this;
    }

    public function permissionsResourceNavigationSort(?int $sort): static
    {
        $this->permissionsResourceOptions['navigation_sort'] = $sort;

        return $this;
    }

    public function permissionsResourceModelLabel(?string $label): static
    {
        $this->permissionsResourceOptions['model_label'] = $label;

        return $this;
    }

    public function permissionsResourcePluralModelLabel(?string $label): static
    {
        $this->permissionsResourceOptions['plural_model_label'] = $label;

        return $this;
    }

    public function permissionsResourceManagedPanel(string | \BackedEnum | null $panel): static
    {
        $this->permissionsResourceOptions['managed_panel'] = $panel;

        return $this;
    }

    /**
     * @param  class-string<Cluster>|null  $cluster
     */
    public function permissionsResourceCluster(?string $cluster): static
    {
        $this->permissionsResourceOptions['cluster'] = $cluster;

        return $this;
    }

    public function groupByNavigationGroup(bool $condition = true): static
    {
        $this->permissionsResourceOptions['sections']['group_by_navigation_group'] = $condition;

        return $this;
    }

    public function usesGroupByNavigationGroup(): bool
    {
        return (bool) ($this->permissionsResourceOptions['sections']['group_by_navigation_group']
            ?? config('filament-acl.resources.permissions.sections.group_by_navigation_group', true));
    }

    public function groupByCluster(bool $condition = true): static
    {
        $this->permissionsResourceOptions['sections']['group_by_cluster'] = $condition;

        return $this;
    }

    public function usesGroupByCluster(): bool
    {
        return (bool) ($this->permissionsResourceOptions['sections']['group_by_cluster']
            ?? config('filament-acl.resources.permissions.sections.group_by_cluster', true));
    }

    public function innerTabsVertical(bool $condition = true): static
    {
        $this->permissionsResourceOptions['inner_tabs']['vertical'] = $condition;

        return $this;
    }

    public function usesInnerTabsVertical(): bool
    {
        return (bool) ($this->permissionsResourceOptions['inner_tabs']['vertical']
            ?? config('filament-acl.resources.permissions.inner_tabs.vertical', false));
    }

    public function sectionsCollapsed(bool $condition = true): static
    {
        $this->permissionsResourceOptions['sections']['collapsed'] = $condition;

        return $this;
    }

    public function usesSectionsCollapsed(): bool
    {
        return (bool) ($this->permissionsResourceOptions['sections']['collapsed']
            ?? config('filament-acl.resources.permissions.sections.collapsed', false));
    }

    public function sectionsPersistCollapsed(bool $condition = true): static
    {
        $this->permissionsResourceOptions['sections']['persist_collapsed'] = $condition;

        return $this;
    }

    public function usesSectionsPersistCollapsed(): bool
    {
        return (bool) ($this->permissionsResourceOptions['sections']['persist_collapsed']
            ?? config('filament-acl.resources.permissions.sections.persist_collapsed', true));
    }

    protected function makePermissionsResourceConfiguration(): PermissionResourceConfiguration
    {
        /** @var PermissionResourceConfiguration $configuration */
        $configuration = PermissionResource::make('filament-acl-permissions');

        /** @var array<string, mixed> $defaults */
        $defaults = [
            ...((array) config('filament-acl.resources.permissions', [])),
            ...$this->permissionsResourceOptions,
        ];

        if (filled($defaults['slug'] ?? null)) {
            $configuration->slug((string) $defaults['slug']);
        }

        if (array_key_exists('navigation_label', $defaults)) {
            $configuration->navigationLabel($defaults['navigation_label']);
        }

        if (array_key_exists('navigation_icon', $defaults)) {
            $configuration->navigationIcon($defaults['navigation_icon']);
        }

        if (array_key_exists('navigation_group', $defaults)) {
            $configuration->navigationGroup($defaults['navigation_group']);
        }

        if (array_key_exists('navigation_sort', $defaults)) {
            $configuration->navigationSort($defaults['navigation_sort']);
        }

        if (array_key_exists('model_label', $defaults)) {
            $configuration->modelLabel($defaults['model_label']);
        }

        if (array_key_exists('plural_model_label', $defaults)) {
            $configuration->pluralModelLabel($defaults['plural_model_label']);
        }

        if (array_key_exists('managed_panel', $defaults)) {
            $configuration->managedPanel($defaults['managed_panel']);
        }

        if (array_key_exists('cluster', $defaults)) {
            $configuration->cluster($defaults['cluster']);
        }

        if ($this->configurePermissionsResourceUsing instanceof Closure) {
            ($this->configurePermissionsResourceUsing)($configuration);
        }

        return $configuration;
    }
}
