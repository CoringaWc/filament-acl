<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Widgets\Concerns;

use CoringaWc\FilamentAcl\Attributes\CustomPermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionActions as PermissionActionsAttribute;
use CoringaWc\FilamentAcl\Attributes\PermissionPanel as PermissionPanelAttribute;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject as PermissionSubjectAttribute;
use CoringaWc\FilamentAcl\Attributes\RegisterPermissions;
use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;
use CoringaWc\FilamentAcl\Contracts\BuildsPermissionKey;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\PermissionActionResolver;
use CoringaWc\FilamentAcl\Support\PermissionAttributeReader;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Facades\Filament;
use Throwable;

trait HasWidgetPermissions
{
    public static function getPermissionSubject(): ?string
    {
        return PermissionAttributeReader::read(static::class, PermissionSubjectAttribute::class)?->subject;
    }

    public static function shouldRegisterPermissions(): bool
    {
        $attribute = PermissionAttributeReader::read(static::class, RegisterPermissions::class);

        if ($attribute !== null) {
            return $attribute->register;
        }

        return true;
    }

    /**
     * @return class-string|null
     */
    public static function getPermissionOwnerClass(): ?string
    {
        return null;
    }

    /**
     * @return class-string|null
     */
    public static function getSharedPermissionOwner(): ?string
    {
        $attribute = PermissionAttributeReader::read(static::class, SharedPermissionOwner::class);

        if ($attribute !== null) {
            return $attribute->ownerClass;
        }

        return static::getPermissionOwnerClass();
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array
    {
        $attribute = PermissionAttributeReader::read(static::class, CustomPermissionActions::class);

        if ($attribute !== null) {
            return $attribute->actions;
        }

        return [];
    }

    public static function getPermissionPanel(): ?string
    {
        $attribute = PermissionAttributeReader::read(static::class, PermissionPanelAttribute::class);

        if ($attribute !== null) {
            return $attribute->panel;
        }

        return Filament::getCurrentPanel()?->getId();
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionActions(): array
    {
        if (! static::shouldRegisterPermissions()) {
            return [];
        }

        $attribute = PermissionAttributeReader::read(static::class, PermissionActionsAttribute::class);

        if ($attribute !== null) {
            return $attribute->actions;
        }

        $sharedPermissionOwner = static::resolvePermissionOwnerClass();

        if (($sharedPermissionOwner !== static::class) && method_exists($sharedPermissionOwner, 'getPermissionActions')) {
            /** @var array<int, string> $sharedActions */
            $sharedActions = $sharedPermissionOwner::getPermissionActions();

            return $sharedActions;
        }

        return array_values(array_unique([
            'view',
            ...static::getPermissionCustomActions(),
        ]));
    }

    public static function canView(): bool
    {
        $action = static::getPermissionAction('view');

        if ($action === null) {
            return true;
        }

        $user = static::resolvePermissionUser();

        if (! is_object($user) || ! method_exists($user, 'can')) {
            return false;
        }

        $resolvedAction = app(PermissionActionResolver::class)->resolve(
            ability: 'view',
            permissionAction: $action,
            panelId: static::getPermissionPanel(),
        );

        if ($resolvedAction === null) {
            return true;
        }

        $permissionKey = app(BuildsPermissionKey::class)->build('view', $resolvedAction);

        return $user->can($permissionKey);
    }

    protected static function getPermissionAction(
        ?string $permissionAction = null,
    ): ?PermissionAction {
        if (! static::shouldRegisterPermissions()) {
            return null;
        }

        $permissionOwnerClass = static::resolvePermissionOwnerClass();
        $panelId = static::getPermissionPanel();
        $subject = app(ResolvesPermissionSubject::class)->resolve(
            entityClass: $permissionOwnerClass,
            entityType: PermissionEntityType::Widget,
            panelId: $panelId,
        );

        return PermissionAction::fromOwnerClass(
            ownerClass: $permissionOwnerClass,
            ownerType: PermissionEntityType::Widget,
            subject: $subject,
            permissionAction: $permissionAction,
            panelId: $panelId,
        );
    }

    protected static function getPermissionGateArgument(?string $permissionAction = null): PermissionAction | string | null
    {
        return static::getPermissionAction($permissionAction);
    }

    protected static function resolvePermissionUser(): mixed
    {
        try {
            return Filament::auth()->user();
        } catch (Throwable) {
            return auth()->user();
        }
    }

    /**
     * @return class-string
     */
    protected static function resolvePermissionOwnerClass(): string
    {
        return Utils::resolvePermissionOwnerClass(static::class);
    }
}
