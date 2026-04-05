<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Livewire;

use Filament\Livewire\Topbar as BaseTopbar;
use Workbench\App\Filament\Concerns\EnsuresErrorBag;

class Topbar extends BaseTopbar
{
    use EnsuresErrorBag;
}
