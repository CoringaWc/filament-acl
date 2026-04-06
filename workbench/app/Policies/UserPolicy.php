<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use Illuminate\Auth\Access\Response;
use Workbench\App\Models\User;

class UserPolicy
{
    use ChecksPermission;

    public function viewAny(User $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'viewAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function view(User $user, User $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'view', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function update(User $user, User $record): Response
    {
        return Response::allow();
    }
}
