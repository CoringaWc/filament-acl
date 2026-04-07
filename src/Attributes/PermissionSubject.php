<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Override the permission subject string for a Filament owner (Resource, RelationManager, Page, Widget).
 *
 * Usage:
 *   #[PermissionSubject('custom-subject')]
 *   class PostResource extends Resource { use HasResourcePermissions; }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PermissionSubject
{
    public function __construct(
        public readonly ?string $subject = null,
    ) {}
}
