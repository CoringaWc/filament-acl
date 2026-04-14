<?php

declare(strict_types=1);

namespace CoringaWc\FilamentAcl\Attributes;

use Attribute;

/**
 * Define the complete permission action list for a Filament owner.
 *
 * Usage:
 *   #[PermissionActions(['view'])]
 *   class MyWalletResource extends Resource { use HasResourcePermissions; }
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class PermissionActions
{
    /**
     * @param  array<int, string>  $actions
     */
    public function __construct(
        public readonly array $actions,
    ) {}
}
