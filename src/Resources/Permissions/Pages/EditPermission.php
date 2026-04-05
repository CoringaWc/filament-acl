<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Permissions\Pages;

use CoringaWc\FilamentAcl\Concerns\EnsuresValidationErrorBag;
use CoringaWc\FilamentAcl\Resources\Permissions\PermissionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role as RoleContract;

class EditPermission extends EditRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PermissionResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->resetErrorBag();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Model&RoleContract $record */
        $record = $this->record;

        $assignedPermissionIds = $record->permissions()->pluck('id')->all();

        return [
            ...$data,
            ...static::getResource()::fillPermissionGroupState($assignedPermissionIds),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Model&RoleContract $record */
        $permissionIds = static::getResource()::extractPermissionIdsFromData($data);

        unset($data['permission_groups']);

        $record->update($data);
        $record->permissions()->sync($permissionIds);

        return $record;
    }

    /**
     * @return array<int, DeleteAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
