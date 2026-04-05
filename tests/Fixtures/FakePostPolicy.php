<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Tests\Fixtures;

use CoringaWc\FilamentAcl\Policies\Concerns\ChecksPermission;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use Illuminate\Auth\Access\Response;

class FakePostPolicy
{
    use ChecksPermission;

    public function viewAny(FakeUser $user, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'viewAny', $permissionAction)) {
            return $response;
        }

        return Response::allow();
    }

    public function update(FakeUser $user, FakePost $post, PermissionAction | string | null $permissionAction = null): Response
    {
        if ($response = $this->denyUnlessPermitted($user, 'update', $permissionAction)) {
            return $response;
        }

        if ((bool) $post->getAttribute('locked')) {
            return Response::deny('Locked post.');
        }

        return Response::allow();
    }
}
