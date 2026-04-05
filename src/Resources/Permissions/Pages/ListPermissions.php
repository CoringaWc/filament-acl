<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Permissions\Pages;

use CoringaWc\FilamentAcl\Concerns\EnsuresValidationErrorBag;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissions extends ListRecords
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PermissionResource::class;

    /**
     * @return array<int, CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }
}
