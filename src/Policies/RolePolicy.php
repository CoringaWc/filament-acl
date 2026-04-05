<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Policies;

use CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\Utils;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Contracts\Role as RoleContract;

class RolePolicy
{
    use ChecksPermission;

    public function viewAny(mixed $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'viewAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function view(
        mixed $user,
        RoleContract $record,
        PermissionAction | string | null $permissionAction = null,
    ): Response {
        if ($response = $this->denyUnlessPermitted($user, 'view', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function create(mixed $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'create', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function update(
        mixed $user,
        RoleContract $record,
        PermissionAction | string | null $permissionAction = null,
    ): Response {
        if (Utils::isProtectedRole($record->name)) {
            return Response::deny('The protected role cannot be modified.');
        }

        if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function delete(
        mixed $user,
        RoleContract $record,
        PermissionAction | string | null $permissionAction = null,
    ): Response {
        if (Utils::isProtectedRole($record->name)) {
            return Response::deny('The protected role cannot be deleted.');
        }

        if ($response = $this->denyUnlessPermitted($user, 'delete', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function deleteAny(mixed $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'deleteAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }
}
