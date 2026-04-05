<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Contracts;

use CoringaWc\FilamentAcl\Enums\PermissionEntityType;

interface ResolvesPermissionSubject
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function resolve(
        string $entityClass,
        PermissionEntityType $entityType,
        ?string $panelId = null,
        ?string $registrationKey = null,
        array $meta = [],
    ): string;
}
