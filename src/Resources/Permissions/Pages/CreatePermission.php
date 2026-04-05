<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Permissions\Pages;

use CoringaWc\FilamentAcl\Concerns\EnsuresValidationErrorBag;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePermission extends CreateRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PermissionResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $permissionIds = static::getResource()::extractPermissionIdsFromData($data);

        unset($data['permission_groups']);

        $record = static::getResource()::getModel()::query()->create($data);
        $record->permissions()->sync($permissionIds);

        return $record;
    }
}
