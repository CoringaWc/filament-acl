<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentAclPlugin;
use Filament\Clusters\Cluster;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\RelationManagers\RelationManagerConfiguration;
use Filament\Resources\Resource;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class PermissionOwnerDiscovery
{
    /**
     * @return array<int, PermissionOwnerRegistration>
     */
    public function discoverResources(Panel $panel): array
    {
        $registrations = [];

        foreach ($panel->getResources() as $resourceClass) {
            if (! is_subclass_of($resourceClass, Resource::class)) {
                continue;
            }

            $registration = $this->makeResourceRegistration($panel, $resourceClass);

            if ($registration instanceof PermissionOwnerRegistration) {
                $registrations[] = $registration;
            }
        }

        foreach ($panel->getResourceConfigurations() as $configuration) {
            $registration = $this->makeResourceRegistration(
                panel: $panel,
                resourceClass: $configuration->getResource(),
                registrationKey: $configuration->getKey(),
            );

            if ($registration instanceof PermissionOwnerRegistration) {
                $registrations[] = $registration;
            }
        }

        return $this->uniqueRegistrations($registrations);
    }

    /**
     * @return array<int, PermissionOwnerRegistration>
     */
    public function discoverRelationManagers(Panel $panel, PermissionOwnerRegistration $resourceRegistration): array
    {
        if (! is_subclass_of($resourceRegistration->ownerClass, Resource::class)) {
            return [];
        }

        /** @var array<mixed> $relations */
        $relations = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): array => $this->evaluateResourceWithConfiguration(
                $resourceRegistration->ownerClass,
                $resourceRegistration->registrationKey,
                static fn (): array => $resourceRegistration->ownerClass::getRelations(),
            ),
        );

        $registrations = [];
        /** @var array<int, class-string<RelationManager>> $excludedRelationManagers */
        $excludedRelationManagers = config('filament-acl.relation_managers.exclude', []);

        foreach ($this->extractRelationManagerClasses($relations) as $relationManagerClass) {
            if (in_array($relationManagerClass, $excludedRelationManagers, true) || ! Utils::shouldDisplayPermissionOwner($relationManagerClass)) {
                continue;
            }

            $ownerRecord = $this->makeRelationManagerOwnerRecord($panel, $resourceRegistration);
            $pageClass = $this->resolveRelationManagerPageClass($panel, $resourceRegistration);
            $label = $this->resolveRelationManagerLabel($relationManagerClass, $ownerRecord, $pageClass);
            $icon = $this->resolveRelationManagerIcon($relationManagerClass, $ownerRecord, $pageClass);
            $relatedResource = $relationManagerClass::getRelatedResource();

            $registrations[] = new PermissionOwnerRegistration(
                ownerClass: $relationManagerClass,
                ownerType: PermissionEntityType::RelationManager,
                panelId: $panel->getId(),
                registrationKey: $resourceRegistration->registrationKey,
                label: $label,
                sectionLabel: $resourceRegistration->label,
                relatedResource: is_string($relatedResource) ? $relatedResource : null,
                meta: [
                    'icon' => $icon,
                    'resource_owner_class' => $resourceRegistration->ownerClass,
                    'resource_registration_key' => $resourceRegistration->registrationKey,
                ],
            );
        }

        return $this->uniqueRegistrations($registrations);
    }

    /**
     * @return array<int, PermissionOwnerRegistration>
     */
    public function discoverPages(Panel $panel): array
    {
        $registrations = [];
        /** @var array<int, class-string<Page>> $excludedPages */
        $excludedPages = config('filament-acl.pages.exclude', []);

        foreach ($panel->getPages() as $pageClass) {
            if (! is_subclass_of($pageClass, Page::class)) {
                continue;
            }

            if (in_array($pageClass, $excludedPages, true) || ! Utils::shouldDisplayPermissionOwner($pageClass)) {
                continue;
            }

            $registrations[] = new PermissionOwnerRegistration(
                ownerClass: $pageClass,
                ownerType: PermissionEntityType::Page,
                panelId: $panel->getId(),
                label: $this->resolvePageLabel($panel, $pageClass),
                sectionLabel: __('filament-acl::filament-acl.resources.permissions.tabs.pages'),
            );
        }

        foreach ($panel->getPageConfigurations() as $configuration) {
            $pageClass = $configuration->page;

            if (in_array($pageClass, $excludedPages, true) || ! Utils::shouldDisplayPermissionOwner($pageClass)) {
                continue;
            }

            $registrations[] = new PermissionOwnerRegistration(
                ownerClass: $pageClass,
                ownerType: PermissionEntityType::Page,
                panelId: $panel->getId(),
                registrationKey: $configuration->getKey(),
                label: $this->resolvePageLabel($panel, $pageClass, $configuration->getKey()),
                sectionLabel: __('filament-acl::filament-acl.resources.permissions.tabs.pages'),
            );
        }

        return $this->uniqueRegistrations($registrations);
    }

    /**
     * @return array<int, PermissionOwnerRegistration>
     */
    public function discoverWidgets(Panel $panel): array
    {
        $registrations = [];
        /** @var array<int, class-string<Widget>> $excludedWidgets */
        $excludedWidgets = config('filament-acl.widgets.exclude', []);

        foreach ($panel->getWidgets() as $widget) {
            $widgetClass = match (true) {
                is_string($widget) => $widget,
                default => $widget->widget,
            };

            if (! is_subclass_of($widgetClass, Widget::class)) {
                continue;
            }

            if (in_array($widgetClass, $excludedWidgets, true) || ! Utils::shouldDisplayPermissionOwner($widgetClass)) {
                continue;
            }

            $registrations[] = new PermissionOwnerRegistration(
                ownerClass: $widgetClass,
                ownerType: PermissionEntityType::Widget,
                panelId: $panel->getId(),
                label: $this->resolveWidgetLabel($widgetClass),
                sectionLabel: __('filament-acl::filament-acl.resources.permissions.tabs.widgets'),
            );
        }

        return $this->uniqueRegistrations($registrations);
    }

    protected function makeResourceRegistration(
        Panel $panel,
        string $resourceClass,
        ?string $registrationKey = null,
    ): ?PermissionOwnerRegistration {
        if (! is_subclass_of($resourceClass, Resource::class)) {
            return null;
        }

        if (! Utils::shouldDisplayPermissionOwner($resourceClass)) {
            return null;
        }

        return new PermissionOwnerRegistration(
            ownerClass: $resourceClass,
            ownerType: PermissionEntityType::Resource,
            panelId: $panel->getId(),
            registrationKey: $registrationKey,
            label: $this->resolveResourceLabel($panel, $resourceClass, $registrationKey),
            sectionLabel: $this->resolveResourceSectionLabel($panel, $resourceClass, $registrationKey),
        );
    }

    /**
     * @param  array<mixed>  $relations
     * @return array<int, class-string<RelationManager>>
     */
    protected function extractRelationManagerClasses(array $relations): array
    {
        $classes = [];

        foreach ($relations as $relation) {
            if (is_string($relation) && is_subclass_of($relation, RelationManager::class)) {
                $classes[] = $relation;

                continue;
            }

            if ($relation instanceof RelationManagerConfiguration) {
                $classes[] = $relation->relationManager;

                continue;
            }

            if ($relation instanceof RelationGroup) {
                $classes = [
                    ...$classes,
                    ...$this->extractRelationManagerClasses($relation->getManagers()),
                ];

                continue;
            }

            if (is_array($relation)) {
                $classes = [
                    ...$classes,
                    ...$this->extractRelationManagerClasses($relation),
                ];
            }
        }

        /** @var array<int, class-string<RelationManager>> $unique */
        $unique = array_values(array_unique(array_filter(
            $classes,
            static fn (mixed $class): bool => is_subclass_of($class, RelationManager::class),
        )));

        return $unique;
    }

    protected function resolveResourceLabel(Panel $panel, string $resourceClass, ?string $registrationKey = null): string
    {
        /** @var string $label */
        $label = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): string => $this->evaluateResourceWithConfiguration(
                $resourceClass,
                $registrationKey,
                static fn (): string => (string) $resourceClass::getNavigationLabel(),
            ),
        );

        return filled($label)
            ? $label
            : Str::headline(Str::beforeLast(class_basename($resourceClass), 'Resource'));
    }

    protected function resolveResourceSectionLabel(Panel $panel, string $resourceClass, ?string $registrationKey = null): string
    {
        $groupByCluster = $this->getPluginOption('usesGroupByCluster', true);
        $groupByNavigationGroup = $this->getPluginOption('usesGroupByNavigationGroup', true);

        /** @var class-string<Cluster>|null $cluster */
        $cluster = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): ?string => $this->evaluateResourceWithConfiguration(
                $resourceClass,
                $registrationKey,
                static fn (): ?string => $resourceClass::getCluster(),
            ),
        );

        if ($groupByCluster && ($cluster !== null) && is_subclass_of($cluster, Cluster::class)) {
            return $cluster::getNavigationLabel();
        }

        $navigationGroup = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): mixed => $this->evaluateResourceWithConfiguration(
                $resourceClass,
                $registrationKey,
                static fn (): mixed => $resourceClass::getNavigationGroup(),
            ),
        );

        $navigationLabel = (string) $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): string => $this->evaluateResourceWithConfiguration(
                $resourceClass,
                $registrationKey,
                static fn (): string => (string) $resourceClass::getNavigationLabel(),
            ),
        );

        if ($groupByNavigationGroup && ($navigationGroup !== null)) {
            return (string) match (true) {
                $navigationGroup instanceof \BackedEnum => $navigationGroup->value,
                $navigationGroup instanceof \UnitEnum => $navigationGroup->name,
                is_string($navigationGroup) => $navigationGroup,
                default => $navigationLabel,
            };
        }

        return $navigationLabel;
    }

    protected function resolvePageLabel(Panel $panel, string $pageClass, ?string $registrationKey = null): string
    {
        /** @var string $label */
        $label = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): string => $this->evaluatePageWithConfiguration(
                $pageClass,
                $registrationKey,
                static fn (): string => method_exists($pageClass, 'getNavigationLabel')
                    ? (string) $pageClass::getNavigationLabel()
                    : Str::headline(Str::beforeLast(class_basename($pageClass), 'Page')),
            ),
        );

        return filled($label)
            ? $label
            : Str::headline(Str::beforeLast(class_basename($pageClass), 'Page'));
    }

    protected function resolveWidgetLabel(string $widgetClass): string
    {
        if (method_exists($widgetClass, 'getHeading')) {
            try {
                $heading = $widgetClass::getHeading();

                if (is_string($heading) && filled($heading)) {
                    return $heading;
                }
            } catch (Throwable) {
                //
            }
        }

        return Str::headline(Str::beforeLast(class_basename($widgetClass), 'Widget'));
    }

    protected function resolveRelationManagerLabel(string $relationManagerClass, ?Model $ownerRecord = null, ?string $pageClass = null): string
    {
        if (($ownerRecord instanceof Model) && filled($pageClass) && method_exists($relationManagerClass, 'getTitle')) {
            try {
                $title = $relationManagerClass::getTitle($ownerRecord, $pageClass);

                if (filled($title)) {
                    return (string) $title;
                }
            } catch (Throwable) {
                //
            }
        }

        if (method_exists($relationManagerClass, 'getRelationshipTitle')) {
            try {
                $title = $relationManagerClass::getRelationshipTitle();

                if (filled($title)) {
                    return (string) $title;
                }
            } catch (Throwable) {
                //
            }
        }

        return Str::headline(Str::beforeLast(class_basename($relationManagerClass), 'RelationManager'));
    }

    protected function resolveRelationManagerIcon(string $relationManagerClass, ?Model $ownerRecord = null, ?string $pageClass = null): mixed
    {
        if (($ownerRecord instanceof Model) && filled($pageClass) && method_exists($relationManagerClass, 'getIcon')) {
            try {
                return $relationManagerClass::getIcon($ownerRecord, $pageClass);
            } catch (Throwable) {
                //
            }
        }

        return null;
    }

    protected function makeRelationManagerOwnerRecord(Panel $panel, PermissionOwnerRegistration $resourceRegistration): ?Model
    {
        if (! is_subclass_of($resourceRegistration->ownerClass, Resource::class)) {
            return null;
        }

        /** @var class-string<Model>|null $modelClass */
        $modelClass = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): string => $this->evaluateResourceWithConfiguration(
                $resourceRegistration->ownerClass,
                $resourceRegistration->registrationKey,
                static fn (): string => $resourceRegistration->ownerClass::getModel(),
            ),
        );

        if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
            return null;
        }

        return new $modelClass;
    }

    protected function resolveRelationManagerPageClass(Panel $panel, PermissionOwnerRegistration $resourceRegistration): ?string
    {
        if (! is_subclass_of($resourceRegistration->ownerClass, Resource::class)) {
            return null;
        }

        /** @var array<string, PageRegistration> $pages */
        $pages = $this->evaluateInPanel(
            panel: $panel,
            callback: fn (): array => $this->evaluateResourceWithConfiguration(
                $resourceRegistration->ownerClass,
                $resourceRegistration->registrationKey,
                static fn (): array => $resourceRegistration->ownerClass::getPages(),
            ),
        );

        foreach (['view', 'edit', 'index', 'create'] as $pageName) {
            $pageRegistration = $pages[$pageName] ?? null;

            if ($pageRegistration !== null) {
                return $pageRegistration->getPage();
            }
        }

        foreach ($pages as $pageRegistration) {
            return $pageRegistration->getPage();
        }

        return null;
    }

    protected function evaluateResourceWithConfiguration(
        string $resourceClass,
        ?string $registrationKey,
        \Closure $callback,
    ): mixed {
        if ($registrationKey === null) {
            return $callback();
        }

        return $resourceClass::withConfiguration($registrationKey, $callback);
    }

    protected function evaluatePageWithConfiguration(
        string $pageClass,
        ?string $registrationKey,
        \Closure $callback,
    ): mixed {
        if ($registrationKey === null) {
            return $callback();
        }

        return $pageClass::withConfiguration($registrationKey, $callback);
    }

    protected function evaluateInPanel(Panel $panel, \Closure $callback): mixed
    {
        $previousPanel = Filament::getCurrentPanel();
        $previousResourceConfigurationKey = Filament::getCurrentResourceConfigurationKey();
        $previousPageConfigurationKey = Filament::getCurrentPageConfigurationKey();

        Filament::setCurrentPanel($panel);

        try {
            return $callback();
        } finally {
            Filament::setCurrentPanel($previousPanel);
            Filament::setCurrentResourceConfigurationKey($previousResourceConfigurationKey);
            Filament::setCurrentPageConfigurationKey($previousPageConfigurationKey);
        }
    }

    /**
     * @param  array<int, PermissionOwnerRegistration>  $registrations
     * @return array<int, PermissionOwnerRegistration>
     */
    protected function uniqueRegistrations(array $registrations): array
    {
        return array_values(
            Arr::keyBy($registrations, static fn (PermissionOwnerRegistration $registration): string => $registration->uniqueKey()),
        );
    }

    /**
     * Resolve a plugin option via the fluent API, falling back to config when the plugin is not registered.
     */
    protected function getPluginOption(string $getter, mixed $default): mixed
    {
        try {
            return FilamentAclPlugin::get()->{$getter}();
        } catch (Throwable) {
            return $default;
        }
    }
}
