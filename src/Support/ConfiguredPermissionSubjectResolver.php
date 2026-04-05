<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Contracts\ResolvesPermissionSubject;
use CoringaWc\FilamentAcl\Enums\PermissionEntityType;
use CoringaWc\FilamentAcl\FilamentPermissionManager;

class ConfiguredPermissionSubjectResolver implements ResolvesPermissionSubject
{
    public function __construct(protected FilamentPermissionManager $manager) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function resolve(
        string $entityClass,
        PermissionEntityType $entityType,
        ?string $panelId = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): string {
        $resolvedOwnerClass = Utils::resolvePermissionOwnerClass($entityClass);

        if (method_exists($resolvedOwnerClass, 'getPermissionSubject')) {
            $entitySubject = $resolvedOwnerClass::getPermissionSubject();

            if (is_string($entitySubject) && ($entitySubject !== '')) {
                return $entitySubject;
            }
        }

        $resolvedSubject = $this->manager->resolvePermissionSubject(
            ownerClass: $entityClass,
            ownerType: $entityType,
            panelId: $panelId,
            registrationKey: $registrationKey,
            meta: $meta,
        );

        if (filled($resolvedSubject)) {
            return $resolvedSubject;
        }

        $subjectOverride = config("filament-acl.subject_overrides.{$entityClass}");

        if (is_string($subjectOverride) && ($subjectOverride !== '')) {
            return $subjectOverride;
        }

        return Utils::defaultPermissionSubject(
            entityClass: $resolvedOwnerClass,
            entityType: $entityType,
            registrationKey: $registrationKey,
        );
    }
}
