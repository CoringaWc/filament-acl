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
            return $this->fallbackWhenNoPermissionAction($user, $ability);
        }

        $permissionKey = app(BuildsPermissionKey::class)->build($ability, $resolvedAction);

        if ($user->can($permissionKey)) {
            return Response::allow();
        }

        return Response::deny("Missing permission [{$permissionKey}].");
    }

    /**
     * Called when checkPermission receives no PermissionAction context (i.e. the call
     * did not go through HasResourcePermissions::can() and no PermissionAction was built).
     *
     * By default, reads `filament-acl.policies.null_action_behavior` from config:
     * - 'deny'  (default): safe — denies access rather than silently allowing.
     * - 'allow': original behavior — allows access (useful during gradual migrations).
     *
     * Override this method in a concrete policy to implement custom fallback logic,
     * such as resolving a subject from an associated Resource class.
     */
    protected function fallbackWhenNoPermissionAction(mixed $user, string $ability): Response
    {
        $behavior = config('filament-acl.policies.null_action_behavior', 'deny');

        if ($behavior === 'allow') {
            return Response::allow();
        }

        return Response::deny('No permission context provided for ability [' . $ability . '].');
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
