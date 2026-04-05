<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Categories\Resources\Posts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\Categories\Resources\Posts\PostResource;

class CreatePost extends CreateRecord
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PostResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }
}
