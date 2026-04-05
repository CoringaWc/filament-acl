<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\RelationManagers\Concerns;

use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\Support\DefaultPermissionActionRegistry;
use CoringaWc\FilamentAcl\Support\PermissionAction;
use CoringaWc\FilamentAcl\Support\PermissionGate;
use CoringaWc\FilamentAcl\Support\Utils;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Throwable;

trait HasRelationManagerPermissions
{
    public static function getPermissionSubject(): ?string
    {
        return null;
    }

    public static function shouldRegisterPermissions(): bool
    {
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
        return static::getPermissionOwnerClass();
    }

    /**
     * @return array<int, string>
     */
    public static function getPermissionCustomActions(): array
    {
        return [];
    }

    public static function shouldUseRelatedResourcePermissions(): bool
    {
        return (bool) config('filament-acl.relation_managers.delegate_to_related_resource_by_default', false);
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
            ...app(DefaultPermissionActionRegistry::class)->forRelationManager(),
            ...static::getPermissionCustomActions(),
        ]));
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (static::shouldSkipAuthorization()) {
            return true;
        }

        if (static::shouldUseRelatedResourcePermissions() && ($relatedResource = static::getRelatedResource())) {
            return $relatedResource::canAccess();
        }

        $model = $ownerRecord->{static::getRelationshipName()}()->getQuery()->getModel()::class;

        try {
            return app(PermissionGate::class)->inspect(
                user: static::resolvePermissionUser(),
                ability: 'viewAny',
                target: $model,
                action: static::getPermissionGateArgument('viewAny'),
                shouldCheckPolicyExistence: static::shouldCheckPolicyExistence(),
            )->allowed();
        } catch (AuthorizationException $exception) {
            return $exception->toResponse()->allowed();
        }
    }

    public function getAuthorizationResponse(string $action, ?Model $record = null): Response
    {
        if (static::shouldSkipAuthorization()) {
            return Response::allow();
        }

        if (
            static::shouldUseRelatedResourcePermissions()
            && ($relatedResource = static::getRelatedResource())
            && (blank($record) || ($record::class === $relatedResource::getModel()))
        ) {
            $method = 'get' . lcfirst($action) . 'AuthorizationResponse';

            return method_exists($relatedResource, $method)
                ? $relatedResource::{$method}($record)
                : $relatedResource::getAuthorizationResponse($action, $record);
        }

        return app(PermissionGate::class)->inspect(
            user: static::resolvePermissionUser(),
            ability: $action,
            target: $record ?? $this->getTable()->getModel(),
            action: static::getPermissionGateArgument($action),
            shouldCheckPolicyExistence: static::shouldCheckPolicyExistence(),
        );
    }

    protected function getPermissionAction(
        ?string $permissionAction = null,
        ?string $registrationKey = null,
    ): PermissionAction {
        return static::makePermissionAction($permissionAction, $registrationKey);
    }

    protected static function makePermissionAction(
        ?string $permissionAction = null,
        ?string $registrationKey = null,
    ): PermissionAction {
        $resolvedRegistrationKey = $registrationKey ?? Filament::getCurrentResourceConfigurationKey();
        $panelId = Filament::getCurrentPanel()?->getId();
        $relatedResource = static::getRelatedResource();
        $permissionOwnerClass = static::resolvePermissionOwnerClass();
        $subject = app(ResolvesPermissionSubject::class)->resolve(
            entityClass: $permissionOwnerClass,
            entityType: PermissionEntityType::RelationManager,
            panelId: $panelId,
            registrationKey: $resolvedRegistrationKey,
            meta: array_filter([
                'related_resource' => $relatedResource,
            ], static fn (mixed $value): bool => filled($value)),
        );

        return PermissionAction::forRelationManager(
            relationManagerClass: $permissionOwnerClass,
            subject: $subject,
            permissionAction: $permissionAction,
            panelId: $panelId,
            relatedResource: $relatedResource,
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

        return static::makePermissionAction($permissionAction, $registrationKey);
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
