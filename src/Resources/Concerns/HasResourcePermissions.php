<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Resources\Concerns;

use CoringaWc\FilamentAcl\Attributes\CustomPermissionActions;
use CoringaWc\FilamentAcl\Attributes\PermissionPanel as PermissionPanelAttribute;
use CoringaWc\FilamentAcl\Attributes\PermissionSubject as PermissionSubjectAttribute;
use CoringaWc\FilamentAcl\Attributes\RegisterPermissions;
use CoringaWc\FilamentAcl\Attributes\SharedPermissionOwner;
use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\PermissionAttributeReader;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use UnitEnum;

trait HasResourcePermissions
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

        $sharedPermissionOwner = static::resolvePermissionOwnerClass();

        if (($sharedPermissionOwner !== static::class) && method_exists($sharedPermissionOwner, 'getPermissionActions')) {
            /** @var array<int, string> $sharedActions */
            $sharedActions = $sharedPermissionOwner::getPermissionActions();

            return $sharedActions;
        }

        return array_values(array_unique([
            ...app(DefaultPermissionActionRegistry::class)->forResource(),
            ...static::getPermissionCustomActions(),
        ]));
    }

    protected static function getPermissionRegistrationKey(): ?string
    {
        if (static::hasConfiguration()) {
            return static::getConfiguration()?->getKey();
        }

        return Filament::getCurrentResourceConfigurationKey();
    }

    protected static function getPermissionAction(
        ?string $permissionAction = null,
        ?string $registrationKey = null,
    ): PermissionAction {
        $resolvedRegistrationKey = $registrationKey ?? static::getPermissionRegistrationKey();
        $panelId = static::getPermissionPanel();
        $permissionOwnerClass = static::resolvePermissionOwnerClass();
        $subject = app(ResolvesPermissionSubject::class)->resolve(
            entityClass: $permissionOwnerClass,
            entityType: PermissionEntityType::Resource,
            panelId: $panelId,
            registrationKey: $resolvedRegistrationKey,
        );

        return PermissionAction::forResource(
            resourceClass: $permissionOwnerClass,
            subject: $subject,
            permissionAction: $permissionAction,
            panelId: $panelId,
            registrationKey: $resolvedRegistrationKey,
        );
    }

    protected static function getPermissionGateArgument(
        ?string $permissionAction = null,
        ?string $registrationKey = null,
    ): string | PermissionAction | null {
        if (! static::shouldRegisterPermissions()) {
            return null;
        }

        return static::getPermissionAction($permissionAction, $registrationKey);
    }

    public static function getAuthorizationResponse(string | UnitEnum $action, ?Model $record = null): Response
    {
        if (static::shouldSkipAuthorization()) {
            return Response::allow();
        }

        return app(PermissionGate::class)->inspect(
            user: static::resolvePermissionUser(),
            ability: $action,
            target: $record ?? static::getModel(),
            action: static::getPermissionGateArgument(static::normalizePermissionAbility($action)),
            shouldCheckPolicyExistence: static::shouldCheckPolicyExistence(),
        );
    }

    public static function can(string | UnitEnum $action, ?Model $record = null): bool
    {
        return static::getAuthorizationResponse($action, $record)->allowed();
    }

    public static function authorize(string | UnitEnum $action, ?Model $record = null): ?Response
    {
        return static::getAuthorizationResponse($action, $record)->authorize();
    }

    protected static function normalizePermissionAbility(string | UnitEnum $action): string
    {
        return match (true) {
            $action instanceof UnitEnum => $action->name,
            default => $action,
        };
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
