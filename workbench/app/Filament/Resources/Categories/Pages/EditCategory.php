<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\EditRecord;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Categories\CategoryResource;

class EditCategory extends EditRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = CategoryResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->resetErrorBag();
    }
}
