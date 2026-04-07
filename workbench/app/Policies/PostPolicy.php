<?php

declare(strict_types=1);

namespace Workbench\App\Policies;

use CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use Illuminate\Auth\Access\Response;
use Workbench\App\Models\Post;
use Workbench\App\Models\User;

class PostPolicy
{
    use ChecksPermission;

    public function viewAny(User $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'viewAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function view(User $user, Post $record, PermissionAction | string | null $permissionAction = null): Response
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

    public function update(User $user, Post $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
            return $response;
        }

        if ($record->status === 'locked') {
            return Response::deny('Locked post.');
        }

        return Response::allow();
    }

    public function delete(User $user, Post $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'delete', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function publish(User $user, Post $record, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'publish', $permissionAction)) {
            return $response;
        }

        if ($record->status !== 'draft') {
            return Response::deny('Only draft posts can be published.');
        }

        return Response::allow();
    }
}
