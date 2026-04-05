<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Livewire;

use Filament\Livewire\Sidebar as BaseSidebar;
use Workbench\App\Filament\Concerns\EnsuresErrorBag;

class Sidebar extends BaseSidebar
{
    use EnsuresErrorBag;
}
