<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    use HasFactory;
}
