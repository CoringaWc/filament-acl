<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Permissions;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Resources\ResourceConfiguration;
use UnitEnum;

class PermissionResourceConfiguration extends ResourceConfiguration
{
    protected ?string $navigationLabel = null;

    protected string | BackedEnum | null $navigationIcon = null;

    protected string | UnitEnum | null $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $modelLabel = null;

    protected ?string $pluralModelLabel = null;

    protected string | BackedEnum | null $managedPanel = null;

    /**
     * @var class-string<Cluster>|null
     */
    protected ?string $cluster = null;

    public function navigationLabel(?string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        return $this->navigationLabel;
    }

    public function navigationIcon(string | BackedEnum | null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string | BackedEnum | null
    {
        return $this->navigationIcon;
    }

    public function navigationGroup(string | UnitEnum | null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string | UnitEnum | null
    {
        return $this->navigationGroup;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function modelLabel(?string $label): static
    {
        $this->modelLabel = $label;

        return $this;
    }

    public function getModelLabel(): ?string
    {
        return $this->modelLabel;
    }

    public function pluralModelLabel(?string $label): static
    {
        $this->pluralModelLabel = $label;

        return $this;
    }

    public function getPluralModelLabel(): ?string
    {
        return $this->pluralModelLabel;
    }

    public function managedPanel(string | BackedEnum | null $panel): static
    {
        $this->managedPanel = $panel;

        return $this;
    }

    public function getManagedPanel(): string | BackedEnum | null
    {
        return $this->managedPanel;
    }

    /**
     * @param  class-string<Cluster>|null  $cluster
     */
    public function cluster(?string $cluster): static
    {
        $this->cluster = $cluster;

        return $this;
    }

    /**
     * @return class-string<Cluster>|null
     */
    public function getCluster(): ?string
    {
        return $this->cluster;
    }
}
