<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Categories\CategoryResource;

class CreateCategory extends CreateRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = CategoryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }
}
