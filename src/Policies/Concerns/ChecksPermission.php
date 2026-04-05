<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Policies\Concerns;

use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\PermissionActionResolver;
use Illuminate\Auth\Access\Response;

trait ChecksPermission
{
    protected function checkPermission(
        mixed $user,
        string $ability,
        PermissionAction | string | null $action = null,
    ): Response {
        if (! is_object($user) || ! method_exists($user, 'can')) {
            return Response::deny('Permission checks require an authenticatable user instance.');
        }

        $resolvedAction = $this->resolvePermissionAction($ability, $action);

        if ($resolvedAction === null) {
            return Response::allow();
        }

        $permissionKey = app(BuildsPermissionKey::class)->build($ability, $resolvedAction);

        if ($user->can($permissionKey)) {
            return Response::allow();
        }

        return Response::deny("Missing permission [{$permissionKey}].");
    }

    protected function denyUnlessPermitted(
        mixed $user,
        string $ability,
        PermissionAction | string | null $action = null,
    ): ?Response {
        $response = $this->checkPermission($user, $ability, $action);

        return $response->allowed() ? null : $response;
    }

    protected function resolvePermissionAction(
        string $ability,
        PermissionAction | string | null $action = null,
    ): PermissionAction | string | null {
        return app(PermissionActionResolver::class)->resolve($ability, $action);
    }
}
