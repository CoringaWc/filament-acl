<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Livewire;

use Filament\Livewire\Notifications as BaseNotifications;
use Workbench\App\Filament\Concerns\EnsuresErrorBag;

class Notifications extends BaseNotifications
{
    use EnsuresErrorBag;
}
