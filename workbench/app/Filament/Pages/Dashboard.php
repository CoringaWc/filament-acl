<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Workbench\App\Filament\Concerns\EnsuresValidationErrorBag;

class Dashboard extends BaseDashboard
{
    use EnsuresValidationErrorBag;
}
