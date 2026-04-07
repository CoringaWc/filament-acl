<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Override the panel ID used for permission resolution.
 *
 * Usage:
 *   #[PermissionPanel('admin')]
 *   class CustomPage extends Page { use HasPagePermissions; }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PermissionPanel
{
    public function __construct(
        public readonly ?string $panel = null,
    ) {}
}
