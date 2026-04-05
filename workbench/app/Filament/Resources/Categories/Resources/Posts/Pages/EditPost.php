<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\Resources\Posts\Pages;

use Filament\Resources\Pages\EditRecord;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource;

class EditPost extends EditRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PostResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        $this->resetErrorBag();
    }
}
