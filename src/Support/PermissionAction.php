<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Support;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;

final readonly class PermissionAction
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $ownerClass,
        public PermissionEntityType $ownerType,
        public string $subject,
        public ?string $registrationKey = null,
        public ?string $panelId = null,
        public ?string $permissionAction = null,
        public ?string $relatedResource = null,
        public array $meta = [],
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function forResource(
        string $resourceClass,
        string $subject,
        ?string $permissionAction = null,
        ?string $panelId = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): self {
        return new self(
            ownerClass: $resourceClass,
            ownerType: PermissionEntityType::Resource,
            subject: $subject,
            registrationKey: $registrationKey,
            panelId: $panelId,
            permissionAction: $permissionAction,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function forRelationManager(
        string $relationManagerClass,
        string $subject,
        ?string $permissionAction = null,
        ?string $panelId = null,
        ?string $relatedResource = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): self {
        return new self(
            ownerClass: $relationManagerClass,
            ownerType: PermissionEntityType::RelationManager,
            subject: $subject,
            registrationKey: $registrationKey,
            panelId: $panelId,
            permissionAction: $permissionAction,
            relatedResource: $relatedResource,
            meta: $meta,
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function fromOwnerClass(
        string $ownerClass,
        PermissionEntityType $ownerType,
        string $subject,
        ?string $permissionAction = null,
        ?string $panelId = null,
        ?string $relatedResource = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): self {
        return new self(
            ownerClass: $ownerClass,
            ownerType: $ownerType,
            subject: $subject,
            registrationKey: $registrationKey,
            panelId: $panelId,
            permissionAction: $permissionAction,
            relatedResource: $relatedResource,
            meta: $meta,
        );
    }
}
