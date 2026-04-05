<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    use HasFactory;
}
