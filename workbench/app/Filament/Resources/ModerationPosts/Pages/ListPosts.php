<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\ModerationPosts\Pages;

use Filament\Resources\Pages\ListRecords;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;
use Workbench\App\Filament\Resources\ModerationPosts\PostResource;

class ListPosts extends ListRecords
{
    use EnsuresValidationErrorBag;

    protected static string $resource = PostResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->resetErrorBag();
    }
}
