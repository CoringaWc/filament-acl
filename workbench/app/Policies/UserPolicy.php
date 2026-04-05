<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use Illuminate\Auth\Access\Response;
use Workbench\App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): Response
    {
        return Response::allow();
    }

    public function view(User $user, User $record): Response
    {
        return Response::allow();
    }

    public function update(User $user, User $record): Response
    {
        return Response::allow();
    }
}
