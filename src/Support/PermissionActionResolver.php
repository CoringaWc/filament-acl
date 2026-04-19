<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use Filament\Facades\Filament;
use Illuminate\Contracts\Container\BindingResolutionException;

class PermissionActionResolver
{
    public function __construct(
        protected ResolvesPermissionSubject $permissionSubjectResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function resolve(
        string $ability,
        PermissionAction | string | null $permissionAction = null,
        ?string $panelId = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): PermissionAction | string | null {
        if ($permissionAction === null) {
            return null;
        }

        if ($permissionAction instanceof PermissionAction) {
            return $permissionAction;
        }

        if (! class_exists($permissionAction)) {
            return $permissionAction;
        }

        if (! Utils::shouldRegisterPermissionOwner($permissionAction)) {
            return null;
        }

        $resolvedOwnerClass = Utils::resolvePermissionOwnerClass($permissionAction);
        $ownerType = $this->resolveOwnerType($resolvedOwnerClass);

        if ($ownerType === null) {
            return $permissionAction;
        }

        $resolvedPanelId = $panelId ?? $this->resolveCurrentPanelId();
        $subject = $this->permissionSubjectResolver->resolve(
            entityClass: $resolvedOwnerClass,
            entityType: $ownerType,
            panelId: $resolvedPanelId,
            registrationKey: $registrationKey,
            meta: $meta,
        );

        return PermissionAction::fromOwnerClass(
            ownerClass: $resolvedOwnerClass,
            ownerType: $ownerType,
            subject: $subject,
            permissionAction: $ability,
            panelId: $resolvedPanelId,
            registrationKey: $registrationKey,
            meta: $meta,
        );
    }

    /**
     * @param  class-string  $ownerClass
     */
    protected function resolveOwnerType(string $ownerClass): ?PermissionEntityType
    {
        return Utils::inferPermissionEntityType($ownerClass);
    }

    protected function resolveCurrentPanelId(): ?string
    {
        if (! app()->bound('filament')) {
            return null;
        }

        try {
            return Filament::getCurrentPanel()?->getId();
        } catch (BindingResolutionException) {
            return null;
        }
    }
}
