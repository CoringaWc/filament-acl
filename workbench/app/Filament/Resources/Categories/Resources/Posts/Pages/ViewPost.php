<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\Resources\Posts\Pages;

use Filament\Resources\Pages\ViewRecord;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource;

class ViewPost extends ViewRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PostResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->resetErrorBag();
    }
}
