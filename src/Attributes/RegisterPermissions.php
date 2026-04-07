<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Control whether a Filament owner should register its permissions.
 *
 * Usage:
 *   #[RegisterPermissions(false)]
 *   class InternalResource extends Resource { use HasResourcePermissions; }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class RegisterPermissions
{
    public function __construct(
        public readonly bool $register = true,
    ) {}
}
