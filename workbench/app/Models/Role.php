<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use CoringaWc\FilamentAcl\Models\Role as PluginRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends PluginRole
{
    use HasFactory;
}
