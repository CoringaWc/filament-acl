<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use Illuminate\Auth\Access\Response;
use Workbench\App\Models\Category;
use Workbench\App\Models\User;

class CategoryPolicy
{
    use ChecksPermission;

    public function viewAny(User $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'viewAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function view(User $user, Category $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'view', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function create(User $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'create', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function update(User $user, Category $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function delete(User $user, Category $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'delete', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }
}
